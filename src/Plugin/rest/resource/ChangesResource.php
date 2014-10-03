<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ChangesResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\Changes\Changes;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "get" = "Drupal\relaxed\Changes\ChangesInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   },
 *   uri_parameters = {
 *     "canonical" = {
 *       "db" = {
 *         "type" = "entity_uuid:workspace",
 *       }
 *     }
 *   }
 * )
 */
class ChangesResource extends ResourceBase {

  public function get($workspace) {
    if (is_string($workspace)) {
      throw new BadRequestHttpException(t('Database does not exist'));
    }

    $changes = \Drupal::service('relaxed.changes');
    $result = $changes->useWorkspace($workspace->name())->getNormal();

    return new ResourceResponse($result, 200);
  }

}
