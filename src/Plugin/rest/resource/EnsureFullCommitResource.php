<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\EnsureFullCommitResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
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

  public function post($workspace) {
    if (is_string($workspace)) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }

    $response_data = array(
      'instance_start_time' => (string) $workspace->getStartTime(),
      'ok' => TRUE,
    );

    return new ResourceResponse($response_data, 201);
  }
}
