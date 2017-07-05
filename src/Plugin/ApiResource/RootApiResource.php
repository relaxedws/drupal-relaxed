<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\rest\ResourceResponse;

/**
 * @ApiResource(
 *   id = "root",
 *   label = "Root",
 *   path = ""
 * )
 */
class RootApiResource extends ApiResourceBase {

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
