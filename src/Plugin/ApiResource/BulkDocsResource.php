<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\rest\ModifiedResourceResponse;

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
class BulkDocsResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\replication\BulkDocs\BulkDocsInterface $bulk_docs
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function post($workspace, $bulk_docs) {
    $this->checkWorkspaceExists($workspace);

    $bulk_docs->save();
    return new ModifiedResourceResponse($bulk_docs, 201);
  }

}
