<?php

namespace Drupal\relaxed\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\relaxed\Plugin\ApiResourceManagerInterface;
use Psr\Log\LoggerInterface;
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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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

      // Use the new generator.
      $api_resource_routes = $this->getRoutesForResourceConfig($api_resource);
      $collection->addCollection($api_resource_routes);
    }
  }

}
