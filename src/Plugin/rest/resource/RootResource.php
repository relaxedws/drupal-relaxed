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
      [
        'couchdb' => t('Welcome'),
        'uuid' => \Drupal::config('system.site')->get('uuid'),
        'vendor' => [
          'name' => 'Drupal',
          'version' => \Drupal::VERSION,
        ],
        'version' => \Drupal::VERSION,
      ],
      200
    );
  }
}
