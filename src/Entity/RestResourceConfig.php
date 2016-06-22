<?php

namespace Drupal\relaxed\Entity;

use Drupal\rest\Entity\RestResourceConfig as CoreRestResourceConfig;

class RestResourceConfig extends CoreRestResourceConfig {

  /**
   * {@inheritdoc}
   */
  protected function normalizeRestMethod($method) {
    if (substr($this->get('plugin_id'), 0, 7) === 'relaxed') {
      $valid_methods = ['GET', 'POST', 'PATCH', 'DELETE', 'PUT', 'HEAD'];
      $normalised_method = strtoupper($method);
      if (!in_array($normalised_method, $valid_methods)) {
        throw new \InvalidArgumentException('The method is not supported.');
      }
      return $normalised_method;
    } 
    else {
      return parent::normalizeRestMethod($method);
    }
  }

}
