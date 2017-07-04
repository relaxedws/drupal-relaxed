<?php

namespace Drupal\relaxed\Plugin;

/**
 * API resource route generator.
 */
class ApiResourceRouteGenerator implements ApiResourceRouteGeneratorInterface {

  /**
   * The plugin manager for API resource plugins.
   *
   * @var \Drupal\relaxed\Plugin\ApiResourceManagerInterface
   */
  protected $manager;

  /**
   * Allowed request methods.
   *
   * CouchDB only supports these methods.
   *
   * @var array
   */
  protected $requestMethods = [
    'HEAD',
    'GET',
    'POST',
    'PUT',
    'DELETE',
    // This is a non-standard HTTP method implemented by CouchDB.
    'COPY',
  ];

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
   * @param ApiResourceInterface $api_resource
   * @return RouteCollection
   */
  public function routes(ApiResourceInterface $api_resource) {
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
        '_api_resource' => $plugin_id,
      ], [
        '_permission' => $permissions,
        '_csrf_request_header_token' => 'TRUE'
      ],
        [
          'no_cache' => isset($definition['no_cache']) ? $definition['no_cache'] : FALSE,
        ],
        '',
        [],
        // The HTTP method is a requirement for this route.
        [$method]
      );

      // Check that authentication providers are defined.
      // @todo !! I think we want this to be configured globally for relaxed. !!
      if (empty($api_resource->getAuthenticationProviders($method))) {
        $this->logger->error('At least one authentication provider must be defined for resource @id', [':id' => $rest_resource_config->id()]);
        continue;
      }

      $route->setOption('_auth', $api_resource->getAuthenticationProviders($method));

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
          foreach ($this->availableFormats() as $format) {
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
    return $this->requestMethods;
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
