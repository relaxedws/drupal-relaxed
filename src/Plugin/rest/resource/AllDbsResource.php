<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
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
    $workspace_machine_names = [];
    /** @var \Drupal\multiversion\Entity\WorkspaceInterface $workspace */
    foreach (Workspace::loadMultiple() as $workspace) {
      $workspace_machine_names[] = $workspace->getMachineName();
    }

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheTags(\Drupal::entityTypeManager()->getDefinition('workspace')->getListCacheTags());
    $response = new ResourceResponse($workspace_machine_names, 200);
    $response->addCacheableDependency($cacheable_metadata);
    return $response;
  }
}
