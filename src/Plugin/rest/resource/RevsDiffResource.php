<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\RevsDiffResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\multiversion\Entity\Index\RevisionIndex;
use Drupal\relaxed\RevisionDiff\RevisionDiff;

/**
 * @RestResource(
 *   id = "relaxed:revs_diff",
 *   label = "Revisions diff",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\relaxed\RevisionDiff\RevisionDiffInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_revs_diff",
 *   }
 * )
 */
class RevsDiffResource extends ResourceBase {

  public function post($workspace, $data) {
    if (is_string($workspace)) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }
    if (empty($data)) {
      throw new BadRequestHttpException(t('No content info received'));
    }

    $revs_diff = \Drupal::service('relaxed.revs_diff');
    $missing = $revs_diff->setEntityKeys($data)->getMissing();

    return new ResourceResponse($missing, 200);
  }

}
