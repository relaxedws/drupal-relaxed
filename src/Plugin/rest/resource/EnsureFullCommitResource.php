<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This resource does not do anything since Drupal does not (yet) have a concept
 * of transactions across multiple requests. This resource only exists to comply
 * with the replication protocol.
 *
 * @RestResource(
 *   id = "relaxed:ensure_full_commit",
 *   label = "Ensure Full Commit",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_ensure_full_commit",
 *   }
 * )
 */
class EnsureFullCommitResource extends ResourceBase {

  /**
   * @param $workspace
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function post($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new BadRequestHttpException(t('Invalid workspace name.'));
    }

    $response_data = [
      'ok' => TRUE,
      'instance_start_time' => (string) $workspace->getStartTime(),
    ];

    return new ModifiedResourceResponse($response_data, 201);
  }

}
