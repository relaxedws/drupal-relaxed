<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Remote check plugins.
 */
abstract class RemoteCheckBase extends PluginBase implements RemoteCheckInterface {

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
