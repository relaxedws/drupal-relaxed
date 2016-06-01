<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "relaxed:replicate",
 *   label = "Replicate",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\Replicate\Replicate",
 *   },
 *   uri_paths = {
 *     "canonical" = "/_replicate",
 *   }
 * )
 */
class ReplicateResource extends ResourceBase {

  /**
   * @param \Drupal\replication\Replicate\Replicate $replicate
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($replicate) {
    $replicate->doReplication();

    return new ResourceResponse($replicate, 201);
  }
}
