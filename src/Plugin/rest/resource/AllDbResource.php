<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDbResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @file
 * Implements http://docs.couchdb.org/en/latest/api/server/common.html#all-dbs
 */

/**
 * @RestResource(
 *   id = "relaxed:all_dbs",
 *   label = "All Workspaces",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/_all_dbs",
 *   }
 * )
 */
class AllDbResource extends ResourceBase {

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
