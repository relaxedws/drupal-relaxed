<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "relaxed:all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_all_docs",
 *   }
 * )
 */
class AllDocsResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace) {
    $db = $workspace->id();
    $docs = array();
    $multiversion_manager = \Drupal::service('multiversion.manager');
    $entity_types = \Drupal::service('entity.manager')->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if ($multiversion_manager->isSupportedEntityType($entity_type)) {
        $entities = entity_load_multiple_by_properties($entity_type->id(), array('workspace' => array(array('target_id' => $db))));
        $docs = array_merge($docs, $entities);
      }
    }
    $result = array(
      'total_rows' => count($docs),
      'offset' => 0,
      'rows' => $docs,
    );

    return new ResourceResponse($result, 200);
  }
}
