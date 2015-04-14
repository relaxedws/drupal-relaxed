<?php

/**
 * @file
 * Contains \Drupal\relaxed\RelaxedServiceProvider.
 */

namespace Drupal\relaxed;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Defines a service profiler for the multiversion module.
 */
class RelaxedServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container) {
    // Override the access_check.rest.csrf class with a new class.
    // @todo Revisit this before beta release: https://www.drupal.org/node/2470691
    try {
      $definition = $container->getDefinition('access_check.rest.csrf');
      $definition->setClass('Drupal\relaxed\Access\CSRFAccessCheck');
    }
    catch (InvalidArgumentException $e) {
      // Do nothing, rest module is not installed.
    }
  }

}
