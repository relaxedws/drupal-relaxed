<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
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
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($workspace, $revs_diff) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }
    if (empty($revs_diff)) {
      throw new BadRequestHttpException(t('No content info received'));
    }

    return new ResourceResponse($revs_diff, 200);
  }

}
