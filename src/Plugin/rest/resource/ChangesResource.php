<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ChangesResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\Changes\Changes;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\Changes\Changes",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   }
 * )
 */
class ChangesResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *  The workspace.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception thrown if $workspace is not a loaded entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *  The resource response.
   */
  public function get($workspace) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    // @todo: Inject the container without using deprecated method call.
    $changes = Changes::createInstance(
      \Drupal::getContainer(),
      \Drupal::service('entity.index.sequence'),
      $workspace
    );

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $changes->includeDocs(TRUE);
    }

    return new ResourceResponse($changes, 200);
  }

}
