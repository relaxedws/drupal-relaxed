<?php

/**
 * @file
 * Contains \Drupal\deploy\Annotation\Endpoint.
 */

namespace Drupal\relaxed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Endpoint item annotation object.
 *
 * @see \Drupal\deploy\Plugin\EndpointManager
 * @see plugin_api
 *
 * @Annotation
 */
class Endpoint extends Plugin {

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
