<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\EndpointCheckInterface.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\relaxed\Entity\EndpointInterface;

/**
 * Defines an interface for Endpoint check plugins.
 */
interface EndpointCheckInterface extends PluginInspectionInterface {

  /**
   * Process the check based on the given Endpoint.
   *
   * @param \Drupal\relaxed\Entity\EndpointInterface $endpoint
   * @return mixed
   */
  public function execute(EndpointInterface $endpoint);

  /**
   * Return true if check passes.
   *
   * @return boolean
   */
  public function getResult();

  /**
   * Return a message relating to the check result.
   *
   * @return string
   */
  public function getMessage();

}
