<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\FormatNegotiatorInterface.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Cache\CacheableDependencyInterface;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Contract for format negotiator plugins.
 */
interface FormatNegotiatorInterface extends CacheableDependencyInterface {

  /**
   * Use this applies method OR the in the annotation. This was is
   * potentially more expensive at runtime, but is way more flexible.
   *
   * @param string $format
   * @param string $method
   * @param string $type
   *   Either 'request' or 'response'.
   *
   * @return bool
   *   TRUE if this negotiator applies, FALSE otherwise.
   */
  public function applies($format, $method, $type);

  /**
   * Return a serializer instance.
   *
   * Pretty much every time would be injected into the plugin and just returned.
   *
   * @param string $format
   * @param string $method
   * @param string $type
   *   Either 'request' or 'response'.
   *
   * @return \Symfony\Component\Serializer\Serializer
   */
  public function serializer($format, $method, $type);

}
