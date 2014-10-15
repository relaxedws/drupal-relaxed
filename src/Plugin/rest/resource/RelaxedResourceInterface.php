<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceInterface;

interface RelaxedResourceInterface extends ResourceInterface {

  /**
   * Returns whether or not this is an attachment resource.
   * @return boolean
   */
  public function isAttachment();

}
