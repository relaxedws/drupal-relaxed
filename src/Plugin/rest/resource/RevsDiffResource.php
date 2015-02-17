<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\RevsDiffResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "relaxed:revs_diff",
 *   label = "Revisions diff",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\RevisionDiff\RevisionDiff",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_revs_diff",
 *   }
 * )
 */
class RevsDiffResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\relaxed\RevisionDiff\RevisionDiffInterface $revs_diff
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($workspace, $revs_diff) {
    if (is_string($workspace)) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }
    if (empty($revs_diff)) {
      throw new BadRequestHttpException(t('No content info received'));
    }

    return new ResourceResponse($revs_diff, 200);
  }

}
