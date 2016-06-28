<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\multiversion\Entity\Workspace;

/**
 * Implements http://docs.couchdb.org/en/latest/api/server/common.html#all-dbs
 */

/**
 * @RestResource(
 *   id = "relaxed:all_dbs",
 *   label = "All Workspaces",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\Workspace",
 *   },
 *   uri_paths = {
 *     "canonical" = "/_all_dbs",
 *   }
 * )
 */
class AllDbsResource extends ResourceBase {

  /**
   * Retrieve list of all entity types.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $workspaces = [];
    foreach (Workspace::loadMultiple() as $workspace) {
      $workspaces[] = $workspace->getMachineName();
    }

    return new ResourceResponse($workspaces, 200);
  }
}
