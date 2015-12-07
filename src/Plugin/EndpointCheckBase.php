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
   * @inheritDoc
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * @inheritDoc
   */
  public function getMessage() {
    return $this->message;
  }

}
