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
class RootApiResource extends ApiResourceBase {

  /**
   * @return ResourceResponse
   */
  public function get() {
    $request = \Drupal::request();
    $uuid = MD5($request->getHost() . $request->getPort());
    return new ResourceResponse(
      [
        'couchdb' => t('Welcome'),
        'uuid' => $uuid,
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
