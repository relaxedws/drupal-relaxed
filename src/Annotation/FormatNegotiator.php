<?php

namespace Drupal\relaxed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a format negotiator plugin annotation.
 *
 * @see \Drupal\relaxed\Plugin\FormatNegotiatorManager
 * @see plugin_api
 *
 * @Annotation
 */
class FormatNegotiator extends Plugin {

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

  /**
   * The priority of the negotiator.
   *
   * @var int
   */
  public $priority = 0;

}
