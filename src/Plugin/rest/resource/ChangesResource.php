<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ChangesResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\Changes\ChangesInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   }
 * )
 */
class ChangesResource extends ResourceBase {

  public function get($workspace) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    $changes = \Drupal::service('relaxed.changes');
    $result = $changes->useWorkspace($workspace->id())->getNormal();

    return new ResourceResponse($result, 200);
  }

}
