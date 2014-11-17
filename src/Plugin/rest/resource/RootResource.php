<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\RootResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "relaxed:root",
 *   label = "Root",
 *   uri_paths = {
 *     "canonical" = "",
 *   }
 * )
 */
class RootResource extends ResourceBase {

  /**
   * @return ResourceResponse
   */
  public function get() {
    return new ResourceResponse(
      array(
        'drupal' => t('Welcome'),
        'uuid' => md5($GLOBALS['base_url']),
        'vendor' =>array(
          'name' => 'Drupal',
          'version' => \Drupal::VERSION,
        ),
        'version' => \Drupal::VERSION,
      ),
      200
    );
  }
}
