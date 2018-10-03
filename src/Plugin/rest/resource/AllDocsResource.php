<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\replication\AllDocs\AllDocs;

/**
 * @RestResource(
 *   id = "relaxed:all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\AllDocs\AllDocs",
 *      "post" = "Drupal\multiversion\Entity\WorkspaceInterface"
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_all_docs",
 *     "https://www.drupal.org/link-relations/create" = "/{db}/_all_docs",
 *   },
 *   no_cache = TRUE
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
    $this->checkWorkspaceExists($workspace);

    $all_docs = \Drupal::service('replication.alldocs_factory')->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }
    if ($request->query->get('keys') !== null) {
      $keys = json_decode($request->query->get('keys'));
      $all_docs->keys($keys);
    }

    $response = new ResourceResponse($all_docs, 200);
    foreach (\Drupal::service('multiversion.manager')->getSupportedEntityTypes() as $entity_type) {
      $response->addCacheableDependency($entity_type);
    }
    return $response;
  }

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param object $keys
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function post($workspace) {
    $this->checkWorkspaceExists($workspace);

    $all_docs = \Drupal::service('replication.alldocs_factory')->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }
    if ($request->getContent() !== null) {
      $body = json_decode($request->getContent());
      $all_docs->keys($body->keys);
    }

    $response = new ResourceResponse($all_docs, 200);
    foreach (\Drupal::service('multiversion.manager')->getSupportedEntityTypes() as $entity_type) {
      $response->addCacheableDependency($entity_type);
    }
    return $response;
  }

}
