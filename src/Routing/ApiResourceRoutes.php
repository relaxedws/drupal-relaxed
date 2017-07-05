<?php

namespace Drupal\relaxed\Routing;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\relaxed\Plugin\ApiResourceManagerInterface;
use Drupal\relaxed\Plugin\ApiResourceRouteGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for REST-style routes.
 */
class ApiResourceRoutes extends RouteSubscriberBase {

  /**
   * The plugin manager for API resource plugins.
   *
   * @var \Drupal\relaxed\Plugin\ApiResourceManagerInterface
   */
  protected $manager;

  /**
   * The API resource route generator.
   *
   * @var \Drupal\relaxed\Plugin\ApiResourceRouteGeneratorInterface
   */
  protected $generator;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an ApiResourceRoutes object.
   *
   * @param \Drupal\relaxed\Plugin\ApiResourceManagerInterface $manager
   *   The resource plugin manager.
   * @param \Drupal\relaxed\Plugin\ApiResourceRouteGeneratorInterface $generator
   *   The resource route generator.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   */
  public function __construct(ApiResourceManagerInterface $manager, ApiResourceRouteGeneratorInterface $generator, LoggerChannelInterface $logger) {
    $this->manager = $manager;
    $this->generator = $generator;
    $this->logger = $logger;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @return array
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Iterate over all API resource plugins.
    foreach ($this->manager->getDefinitions() as $definition) {
      /** @var \Drupal\relaxed\Plugin\ApiResourceInterface $api_resource */
      $api_resource = $this->manager->createInstance($definition['id'], $definition);

      // Use the generator.
      $api_resource_routes = $this->generator->routes($api_resource);
      $collection->addCollection($api_resource_routes);
    }
  }

}
