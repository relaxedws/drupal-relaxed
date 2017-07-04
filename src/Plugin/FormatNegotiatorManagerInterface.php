<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface.
 */

namespace Drupal\relaxed\Plugin;

/**
 * Interface for format negotiator plugin manager.
 */
interface FormatNegotiatorManagerInterface {

  /**
   * Selects an appropriate negotiator
   *
   * @param string $format
   * @param string $method
   *
   * @return \Drupal\relaxed\Plugin\FormatNegotiatorInterface|NULL
   */
  public function select($format, $method);

  /**
   * Return a list of all available formats.
   *
   * @return array
   */
  public function availableFormats();

}
