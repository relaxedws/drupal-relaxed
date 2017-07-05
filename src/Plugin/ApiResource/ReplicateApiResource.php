<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\rest\ResourceResponse;

/**
 * @ApiResource(
 *   id = "relaxed:replicate",
 *   label = "Replicate",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\Replicate\Replicate",
 *   },
 *   path = "/_replicate"
 * )
 */
class ReplicateApiResource extends ApiResourceBase {

  /**
   * @param \Drupal\relaxed\Replicate\Replicate $replicate
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($replicate) {
    $replicate->doReplication();

    return new ResourceResponse($replicate, 201);
  }
}
