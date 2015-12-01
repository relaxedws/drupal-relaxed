<?php

/**
 * @file
 * Contains \Drupal\deploy\Plugin\EndpointInterface.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defines an interface for Endpoint plugins.
 */
interface EndpointInterface extends ConfigurablePluginInterface, UriInterface  {

  // Add get/set methods for your plugin type here.

}
