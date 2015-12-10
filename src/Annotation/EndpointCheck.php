<?php

/**
 * @file
 * Contains \Drupal\relaxed\Annotation\EndpointCheck.
 */

namespace Drupal\relaxed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Endpoint check item annotation object.
 *
 * @see \Drupal\relaxed\Plugin\EndpointCheckManager
 * @see plugin_api
 *
 * @Annotation
 */
class EndpointCheck extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
