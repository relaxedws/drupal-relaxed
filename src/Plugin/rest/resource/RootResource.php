<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "relaxed:root",
 *   label = "Root",
 *   uri_paths = {
 *     "canonical" = "",
 *   }
 * )
 */
class RootResource extends ResourceBase {

  /**
   * @return ResourceResponse
   */
  public function get() {
    return new ResourceResponse(
      array(
        'couchdb' => t('Welcome'),
        'uuid' => \Drupal::config('system.site')->get('uuid'),
        'vendor' => array(
          'name' => 'Drupal',
          'version' => \Drupal::VERSION,
        ),
        'version' => \Drupal::VERSION,
      ),
      200
    );
  }
}
