<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * API resource route generator.
 */
class ApiResourceRouteGenerator implements ApiResourceRouteGeneratorInterface {

  /**
   * The plugin manager for format negotiator plugins.
   *
   * @var \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface
   */
  protected $formatManager;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
   * The relaxed API root (base) path.
   *
   * @var string
   */
  protected $apiRoot;

  /**
   * The relaxed configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var array
   */
  protected $availableFormats;

  /**
   * The configured authentication providers for relaxed endpoints.
   *
   * @var array
   */
  protected $authenticationProviders;

  /**
   * Constructs an ApiResourceRoutes object.
   *
   * @param \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface $manager
   *   The format negotiator plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   */
  public function __construct(FormatNegotiatorManagerInterface $format_manager, ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger) {
    $this->formatManager = $format_manager;
    $this->logger = $logger;

    $relaxed_config = $config_factory->get('relaxed.settings');

    $this->apiRoot = trim($relaxed_config->get('api_root'), '/');
    $this->authenticationProviders = $relaxed_config->get('authentication');
  }

  /**
   * @param ApiResourceInterface $api_resource
   * @return RouteCollection
   */
  public function routes(ApiResourceInterface $api_resource) {
    $collection = new RouteCollection();
    $definition = $api_resource->getPluginDefinition();
    $plugin_id = $api_resource->getPluginId();
    // Prefix all routes with 'relaxed.'.
    $route_name = sprintf('relaxed.%s', strtr($plugin_id, ':', '.'));

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
        // @todo We might want to remove this so any format will always return the same...
        // Add all formats we have to allowed responses on the route.
        //'_format' => implode('|', $this->availableFormats()),
      ],
        [
          'no_cache' => isset($definition['no_cache']) ? $definition['no_cache'] : FALSE,
        ],
        '',
        [],
        // The HTTP method is a requirement for this route.
        [$method]
      );

      $route->setOption('_auth', $this->authenticationProviders());
      //$route->addRequirements(['_content_type_format' => implode('|', $this->availableFormats($api_resource))]);

      // @todo {@link https://www.drupal.org/node/2600450 Move this parameter
      // logic to a generic route enhancer instead.}
      $parameters = [];

      foreach (['db', 'docid'] as $parameter) {
        if (strpos($route->getPath(), '{' . $parameter . '}')) {
          $parameters[$parameter] = ['type' => 'relaxed:' . $parameter];
        }
      }

      if (!empty($definition['parameters'])) {
        foreach ($definition['parameters'] as $parameter => $type) {
          $parameters[$parameter] = ['type' => $type];
        }
      }

      if ($parameters) {
        $route->addOptions(['parameters' => $parameters]);
      }

      $collection->add("$route_name.$method_lower", $route);
    }

    return $collection;
  }

  /**
   * Returns the available methods for an API resource plugin.
   *
   * @param ApiResourceInterface $api_resource
   *
   * @return array
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
   * @param \Drupal\relaxed\Plugin\ApiResourceInterface
   *
   * @return array
   */
  protected function availableFormats(ApiResourceInterface $api_resource = NULL) {
    $resource_allowed_formats = $api_resource ? $api_resource->getAllowedFormats() : [];

    if (!isset($this->availableFormats)) {
      $this->availableFormats = $this->formatManager->availableFormats();
    }

    if (empty($resource_allowed_formats)) {
      // Return all formats.
      return $this->availableFormats;
    }

    // Otherwise, intersect them.
    return array_intersect($this->availableFormats, $resource_allowed_formats);
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
   * Returns the configured relaxed authentication providers.
   *
   * @return array
   */
  protected function authenticationProviders() {
    return $this->authenticationProviders;
  }

}
