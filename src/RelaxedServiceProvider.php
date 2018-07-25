<?php

namespace Drupal\relaxed;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Defines a service profiler for the relaxed module.
 */
class RelaxedServiceProvider implements ServiceModifierInterface, ServiceProviderInterface {

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

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new RegisterSerializerCompilerPass());
  }

}
