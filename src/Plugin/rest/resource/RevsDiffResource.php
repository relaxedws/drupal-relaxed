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
 *     "canonical" = "Drupal\relaxed\RevisionDiff\RevisionDiff",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_revs_diff",
 *   }
 * )
 */
class RevsDiffResource extends ResourceBase {

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
