<?php

namespace Drupal\relaxed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Remote check item annotation object.
 *
 * @see \Drupal\relaxed\Plugin\RemoteCheckManager
 * @see plugin_api
 *
 * @Annotation
 */
class RemoteCheck extends Plugin {

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
