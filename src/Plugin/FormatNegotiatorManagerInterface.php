<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for format negotiator plugin manager.
 */
interface FormatNegotiatorManagerInterface extends PluginManagerInterface {

  /**
   * Selects an appropriate negotiator
   *
   * @param string $format
   * @param string $method
   * @param string $type
   *   Either 'request' or 'response'.
   *
   * @return \Drupal\relaxed\Plugin\FormatNegotiatorInterface|NULL
   */
  public function select($format, $method, $type);

  /**
   * Return a list of all available formats.
   *
   * @return array
   */
  public function availableFormats();

}
