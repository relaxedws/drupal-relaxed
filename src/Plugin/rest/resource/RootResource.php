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
