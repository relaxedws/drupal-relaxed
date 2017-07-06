<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\relaxed\Http\ApiResourceResponse;

/**
 * @ApiResource(
 *   id = "root",
 *   label = "Root",
 *   path = ""
 * )
 */
class RootApiResource extends ApiResourceBase {

  /**
   * @return ApiResourceResponse
   */
  public function get() {
    $request = \Drupal::request();
    $uuid = MD5($request->getHost() . $request->getPort());

    return new ApiResourceResponse(
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
