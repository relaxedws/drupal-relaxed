<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\Http\ApiResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @ApiResource(
 *   id = "bulk_docs",
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
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   */
  public function post($workspace, $bulk_docs) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    $bulk_docs->save();
    return new ApiResourceResponse($bulk_docs, 201);
  }
}
