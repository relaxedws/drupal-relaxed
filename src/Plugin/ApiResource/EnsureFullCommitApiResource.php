<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This resource does not do anything since Drupal does not (yet) have a concept
 * of transactions across multiple requests. This resource only exists to comply
 * with the replication protocol.
 *
 * @ApiResource(
 *   id = "relaxed:ensure_full_commit",
 *   label = "Ensure Full Commit",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *   },
 *   path = "/{db}/_ensure_full_commit",
 * )
 */
class EnsureFullCommitApiResource extends ApiResourceBase {

  public function post($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }

    $response_data = [
      'ok' => TRUE,
      'instance_start_time' => (string) $workspace->getStartTime(),
    ];

    return new ResourceResponse($response_data, 201);
  }
}
