<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\relaxed\Http\ApiResourceResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ApiResource(
 *   id = "all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\AllDocs\AllDocs",
 *   },
 *   path = "/{db}/_all_docs"
 * )
 */
class AllDocsApiResource extends ApiResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($workspace) {
    $this->checkWorkspaceExists($workspace);

    $all_docs = \Drupal::service('replication.alldocs_factory')->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }

    $response = new ApiResourceResponse($all_docs, 200);
    foreach (\Drupal::service('multiversion.manager')->getSupportedEntityTypes() as $entity_type) {
      $response->addCacheableDependency($entity_type);
    }
    return $response;
  }

}
