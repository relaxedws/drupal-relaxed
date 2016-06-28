<?php

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
  }

}
