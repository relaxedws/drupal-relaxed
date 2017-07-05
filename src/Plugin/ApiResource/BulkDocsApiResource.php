<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @ApiResource(
 *   id = "relaxed:bulk_docs",
 *   label = "Bulk documents",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\BulkDocs\BulkDocs",
 *   },
 *   path = "/{db}/_bulk_docs"
 * )
 */
class BulkDocsApiResource extends ApiResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\replication\BulkDocs\BulkDocsInterface $bulk_docs
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($workspace, $bulk_docs) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    $bulk_docs->save();
    return new ResourceResponse($bulk_docs, 201);
  }
}
