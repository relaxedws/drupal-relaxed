<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\EndpointCheckBase.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Endpoint check plugins.
 */
abstract class EndpointCheckBase extends PluginBase implements EndpointCheckInterface {

  /**
   * @var bool
   */
  protected $result = false;

  /**
   * @var string
   */
  protected $message = '';

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

}
