<?php

/**
 * @file
 * Contains \Drupal\relaxed\EndpointInterface.
 */

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Endpoint entities.
 */
interface EndpointInterface extends ConfigEntityInterface {
  public function setPlugin($plugin_id);

  public function getPlugin();
}
