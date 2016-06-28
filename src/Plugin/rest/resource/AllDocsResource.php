<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\AllDocs\AllDocs;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\AllDocs\AllDocs",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_all_docs",
 *   }
 * )
 */
class AllDocsResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    $all_docs = \Drupal::service('replication.alldocs_factory')->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }
    return new ResourceResponse($all_docs, 200);
  }

}
