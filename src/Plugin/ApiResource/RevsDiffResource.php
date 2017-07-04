<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @ApiResource(
 *   id = "relaxed:revs_diff",
 *   label = "Revisions diff",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\RevisionDiff\RevisionDiff",
 *   },
 *   path = "/{db}/_revs_diff",
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
    if (!$workspace instanceof WorkspaceInterface || !$workspace->isPublished()) {
      throw new BadRequestHttpException(t('Invalid workspace name.'));
    }
    if (empty($revs_diff)) {
      throw new BadRequestHttpException(t('No content info received.'));
    }

    return new ModifiedResourceResponse($revs_diff, 200);
  }

}
