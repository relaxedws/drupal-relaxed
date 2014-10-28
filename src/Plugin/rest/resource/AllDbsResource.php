<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDbsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;

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
    $workspaces = entity_load_multiple('workspace');
    $workspaces_names = array_keys($workspaces);

    return new ResourceResponse($workspaces_names, 200);
  }
}
