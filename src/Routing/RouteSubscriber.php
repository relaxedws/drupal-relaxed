<?php

namespace Drupal\relaxed\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Performs extra manipulations on routers.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ModuleRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Skips redirect on the relaxed routes if Redirect module is installed.
    if ($this->moduleHandler->moduleExists('redirect')
      && $this->configFactory->get('redirect.settings')->get('route_normalizer_enabled')) {
      foreach ($collection->all() as $name => $route) {
        if (stripos($name, 'relaxed') !== FALSE) {
          $route->setDefault('_disable_route_normalizer', TRUE);
        }
      }
    }
  }

}
