<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\BulkDocsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:bulk_docs",
 *   label = "Bulk documents",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\BulkDocs\BulkDocs",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_bulk_docs",
 *   }
 * )
 */
class BulkDocsResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\relaxed\BulkDocs\BulkDocsInterface $bulk_docs
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($workspace, $bulk_docs) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    $bulk_docs->save();
    return new ResourceResponse($bulk_docs, 201);
  }
}
