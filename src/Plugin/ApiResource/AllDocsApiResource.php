<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    $all_docs = \Drupal::service('replication.alldocs_factory')->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }

    $response = new ResourceResponse($all_docs, 200);
    foreach (\Drupal::service('multiversion.manager')->getSupportedEntityTypes() as $entity_type) {
      $response->addCacheableDependency($entity_type);
    }
    return $response;
  }

}
