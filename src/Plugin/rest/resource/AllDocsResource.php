<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\AllDocs\AllDocs;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "relaxed:all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\AllDocs\AllDocs",
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
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }
    // @todo: Inject the container without using deprecated method call.
    $all_docs = AllDocs::createInstance(
      \Drupal::getContainer(),
      \Drupal::service('entity.manager'),
      \Drupal::service('multiversion.manager'),
      $workspace
    );

    return new ResourceResponse($all_docs, 200);
  }
}
