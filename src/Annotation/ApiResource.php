<?php

namespace Drupal\relaxed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an API resource plugin annotation.
 *
 * @see \Drupal\relaxed\Plugin\FormatNegotiatorManager
 * @see plugin_api
 *
 * @Annotation
 */
class ApiResource extends Plugin {

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
   * The formats allowed by this resource or empty for any format.
   *
   * Formats are defined by format negotiator plugins.
   *
   * @var array
   */
  public $allowed_formats = [];

  /**
   * The path for the resource.
   *
   * @var string
   */
  public $path;

  /**
   * Path parameters.
   *
   * @var array
   */
  public $parameters;

}
