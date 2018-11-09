<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "relaxed:revs_diff",
 *   label = "Revisions diff",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\RevisionDiff\RevisionDiff",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_revs_diff",
 *   },
 *   no_cache = TRUE
 * )
 */
class RevsDiffResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\replication\RevisionDiff\RevisionDiffInterface $revs_diff
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function post($workspace, $revs_diff) {
    try {
      $this->checkWorkspaceExists($workspace);
    }
    catch (\Exception $e) {
      throw new BadRequestHttpException(t('Invalid workspace name.'));
    }
    if (empty($revs_diff)) {
      throw new BadRequestHttpException(t('No content info received.'));
    }

    return new ModifiedResourceResponse($revs_diff, 200);
  }

}
