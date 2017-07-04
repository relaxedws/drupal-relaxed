<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\rest\ResourceResponse;

/**
 * @ApiResource(
 *   id = "relaxed:root",
 *   label = "Root",
 *   path = ""
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
