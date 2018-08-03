<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\relaxed\Http\ApiResourceResponse;
use Drupal\workspaces\Entity\Workspace;

/**
 * Implements http://docs.couchdb.org/en/latest/api/server/common.html#all-dbs
 */

/**
 * @ApiResource(
 *   id = "all_dbs",
 *   label = "All Workspaces",
 *   serialization_class = {
 *     "canonical" = "Drupal\workspaces\Entity\Workspace",
 *   },
 *   path = "/_all_dbs"
 * )
 */
class AllDbsApiResource extends ApiResourceBase {

  /**
   * Retrieve list of all entity types.
   *
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get() {
    /** @var \Drupal\workspaces\WorkspaceInterface[] $workspaces */
    $workspaces = Workspace::loadMultiple();

    $workspace_ids = [];
    foreach ($workspaces as $workspace) {
      $workspace_ids[] = $workspace->id();
    }

    $response = new ApiResourceResponse($workspace_ids, 200);
    foreach ($workspaces as $workspace) {
      $response->addCacheableDependency($workspace);
    }
    $workspace_entity_type = \Drupal::entityTypeManager()->getDefinition('workspace');
    $response->addCacheableDependency((new CacheableMetadata())
      ->addCacheTags($workspace_entity_type->getListCacheTags())
      ->addCacheContexts($workspace_entity_type->getListCacheContexts()));

    return $response;
  }

}
