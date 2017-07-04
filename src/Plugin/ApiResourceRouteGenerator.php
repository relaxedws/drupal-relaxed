<?php

namespace Drupal\relaxed\Plugin;


class ApiResourceRouteGenerator {

  /**
   * The plugin manager for API resource plugins.
   *
   * @var \Drupal\relaxed\Plugin\ApiResourceManagerInterface
   */
  protected $manager;

  /**
   * @var string
   */
  protected $apiRoot;

  /**
   * @var array
   */
  protected $availableFormats;

  /**
   * Constructs an ApiResourceRoutes object.
   *
   * @param \Drupal\relaxed\Plugin\ApiResourceManagerInterface $manager
   *   The resource plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ApiResourceManagerInterface $manager, LoggerInterface $logger) {
    $this->manager = $manager;
    $this->logger = $logger;

    // @todo Inject this, or make a container param instead?
    $this->apiRoot = trim(\Drupal::config('relaxed.settings')->get('api_root'), '/');
  }

  /**
   * Provides all routes for a given REST resource config.
   *
   * This method determines where a resource is reachable, what path
   * replacements are used, the required HTTP method for the operation etc.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_resource_config
   *   The rest resource config.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  protected function getRoutesForResourceConfig(RestResourceConfigInterface $rest_resource_config) {
    $plugin = $rest_resource_config->getResourcePlugin();
    $collection = new RouteCollection();

    foreach ($plugin->routes() as $name => $route) {
      /** @var \Symfony\Component\Routing\Route $route */
      // @todo: Are multiple methods possible here?
      $methods = $route->getMethods();
      // Only expose routes where the method is enabled in the configuration.
      if ($methods && ($method = $methods[0]) && $supported_formats = $rest_resource_config->getFormats($method)) {
        $route->setRequirement('_csrf_request_header_token', 'TRUE');

        // Check that authentication providers are defined.
        if (empty($rest_resource_config->getAuthenticationProviders($method))) {
          $this->logger->error('At least one authentication provider must be defined for resource @id', [':id' => $rest_resource_config->id()]);
          continue;
        }

        // Check that formats are defined.
        if (empty($rest_resource_config->getFormats($method))) {
          $this->logger->error('At least one format must be defined for resource @id', [':id' => $rest_resource_config->id()]);
          continue;
        }

        // If the route has a format requirement, then verify that the
        // resource has it.
        $format_requirement = $route->getRequirement('_format');
        if ($format_requirement && !in_array($format_requirement, $rest_resource_config->getFormats($method))) {
          continue;
        }

        // The configuration has been validated, so we update the route to:
        // - set the allowed request body content types/formats for methods that
        //   allow request bodies to be sent
        // - set the allowed authentication providers
        if (in_array($method, ['POST', 'PATCH', 'PUT'], TRUE)) {
          // Restrict the incoming HTTP Content-type header to the allowed
          // formats.
          $route->addRequirements(['_content_type_format' => implode('|', $this->availableFormats())]);
        }
        $route->setOption('_auth', $rest_resource_config->getAuthenticationProviders($method));
        $route->setDefault('_rest_resource_config', $rest_resource_config->id());
        $collection->add("rest.$name", $route);
      }

    }
    return $collection;
  }

  /**
   * @param ApiResourceInterface $api_resource
   * @return RouteCollection
   */
  public function routes(ApiResourceInterface $api_resource) {
    $this->serializerFormats = array_merge($this->serializerFormats, ['mixed', 'related']);
    $collection = new RouteCollection();
    $definition = $api_resource->getPluginDefinition();
    $plugin_id = $api_resource->getPluginId();
    $route_name = strtr($plugin_id, ':', '.');

    foreach ($this->availableMethods($api_resource) as $method) {
      // HEAD and GET are equivalent as per RFC and handled by the same route.
      // @see \Symfony\Component\Routing\Matcher::matchCollection()
      if ($method == 'HEAD') {
        continue;
      }

      // Allow pull or push permissions depending on the method.
      $permissions = 'perform push replication';
      if ($method === 'GET') {
        $permissions .= '+perform pull replication';
      }

      $method_lower = strtolower($method);
      $route = new Route($this->apiRoot . $definition['path'], [
        '_controller' => 'Drupal\relaxed\Controller\ResourceController::handle',
        '_plugin' => $plugin_id,
      ], [
        '_permission' => $permissions,
      ],
        [
          'no_cache' => isset($definition['no_cache']) ? $definition['no_cache'] : FALSE,
        ],
        '',
        [],
        // The HTTP method is a requirement for this route.
        [$method]
      );

      if (isset($definition['uri_paths'][$method_lower])) {
        $route->setPath($definition['uri_paths'][$method_lower]);
      }

      // @todo {@link https://www.drupal.org/node/2600450 Move this parameter
      // logic to a generic route enhancer instead.}
      $parameters = [];
      foreach (['db', 'docid'] as $parameter) {
        if (strpos($route->getPath(), '{' . $parameter . '}')) {
          $parameters[$parameter] = ['type' => 'relaxed:' . $parameter];
        }
      }
      if (!empty($definition['uri_parameters']['canonical'])) {
        foreach ($definition['uri_parameters']['canonical'] as $parameter => $type) {
          $parameters[$parameter] = ['type' => $type];
        }
      }
      if ($parameters) {
        $route->addOptions(['parameters' => $parameters]);
      }

      switch ($method) {
        case 'POST':
        case 'PUT':
          // Restrict on the Content-Type header.
          if (!$this->isAttachment()) {
            $route->addRequirements(['_content_type_format' => implode('|', $this->availableFormats())]);
          }
          $collection->add("$route_name.$method", $route);
          break;

        case 'GET':
          $collection->add("$route_name.$method", $route);
          break;

        case 'DELETE':
          foreach ($this->serializerFormats as $format) {
            $format_route = clone $route;
            $format_route->addRequirements(['_format' => $format]);
            $collection->add("$route_name.$method.$format", $format_route);
          }
          break;
      }
    }
    return $collection;
  }

  /**
   * Provides predefined HTTP request methods.
   *
   * Plugins can override this method to provide additional custom request
   * methods.
   *
   * @return array
   *   The list of allowed HTTP request method strings.
   */
  protected function requestMethods() {
    // CouchDB only supports these methods.
    return [
      'HEAD',
      'GET',
      'POST',
      'PUT',
      'DELETE',
      // This is a non-standard HTTP method implemented by CouchDB.
      'COPY',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function availableMethods(ApiResourceInterface $api_resource) {
    $methods = $this->requestMethods();
    $available = [];
    foreach ($methods as $method) {
      // Only expose methods where the HTTP request method exists on the plugin.
      if (method_exists($api_resource, strtolower($method))) {
        $available[] = $method;
      }
    }
    return $available;
  }

  /**
   * Returns a list of all available formats.
   *
   * @return array
   */
  protected function availableFormats() {
    if (!isset($this->availableFormats)) {
      $this->availableFormats = $this->manager->availableFormats();
    }

    return $this->availableFormats;
  }

}
