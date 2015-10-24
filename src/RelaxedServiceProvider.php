<?php

/**
 * @file
 * Contains \Drupal\relaxed\RelaxedServiceProvider.
 */

namespace Drupal\relaxed;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a service profiler for the relaxed module.
 */
class RelaxedServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('http_middleware.negotiation')) {
      $negotiation = $container->getDefinition('http_middleware.negotiation');
      // Adds related as known format.
      $negotiation->addMethodCall('registerFormat', ['related', ['multipart/related']]);
      // Adds mixed as known format.
      $negotiation->addMethodCall('registerFormat', ['mixed', ['multipart/mixed']]);
    }

    // Override the access_check.rest.csrf class with a new class.
    // @todo {@link https://www.drupal.org/node/2470691 Revisit this before beta
    // release.}
    try {
      $definition = $container->getDefinition('access_check.rest.csrf');
      $definition->setClass('Drupal\relaxed\Access\CSRFAccessCheck');
    }
    catch (\InvalidArgumentException $e) {
      // Do nothing, rest module is not installed.
    }
  }

}
