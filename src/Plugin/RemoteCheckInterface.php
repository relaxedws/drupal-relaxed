<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\relaxed\Entity\RemoteInterface;

/**
 * Defines an interface for Remote check plugins.
 */
interface RemoteCheckInterface extends PluginInspectionInterface {

  /**
   * Process the check based on the given Remote.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   * @return mixed
   */
  public function execute(RemoteInterface $remote);

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
