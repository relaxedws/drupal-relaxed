<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\EndpointCheckInterface.
 */

namespace Drupal\relaxed;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\relaxed\Entity\EndpointInterface;
/**
 * Defines an interface for Endpoint check plugins.
 */
interface EndpointCheckInterface extends PluginInspectionInterface {

  /**
   * Check to be executed for a given endpoint.
   *
   * @param \Drupal\relaxed\Entity\EndpointInterface $endpoint
   * @return mixed
   */
  public function execute(EndpointInterface $endpoint);

}
