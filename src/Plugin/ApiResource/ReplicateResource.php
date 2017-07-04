<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\rest\ModifiedResourceResponse;

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
class ReplicateResource extends ResourceBase {

  /**
   * @param \Drupal\relaxed\Replicate\Replicate $replicate
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function post($replicate) {
    $replicate->doReplication();

    return new ModifiedResourceResponse($replicate, 200);
  }
}
