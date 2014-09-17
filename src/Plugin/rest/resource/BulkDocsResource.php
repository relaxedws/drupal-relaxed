<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\BulkDocsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * @RestResource(
 *   id = "relaxed:bulk_docs",
 *   derivative_id = "!db",
 *   deriver = "Drupal\relaxed\Plugin\Derivative\BulkDocsDerivative",
 *   label = "!db documents",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/bulk-docs",
 *   },
 *   uri_parameters = {
 *     "canonical" = {
 *       "db" = {
 *         "type" = "entity_uuid",
 *         "rev" = TRUE,
 *       }
 *     }
 *   }
 * )
 */
class BulkDocsResource extends ResourceBase {

  public function post($workspace, array $entities = array()) {
    // If the workspace parameter is a string it means it could not be upcasted
    // to an entity because none existed.
    if (is_string($workspace)) {
      throw new NotFoundHttpException(t('Database does not exist'));
    }
    elseif (empty($entities)) {
      throw new BadRequestHttpException(t('No content info received'));
    }

    // Check for conflicts.
    foreach ($entities as $entity) {
      if ($entity->uuid()) {
        $entry = \Drupal::service('entity.uuid_index')->get($entity->uuid());
        if (!empty($entry)) {
          throw new ConflictHttpException();
        }
      }

      // Check entity and field level access.
      if (!$entity->access('create')) {
        throw new AccessDeniedHttpException();
      }
      foreach ($entity as $field_name => $field) {
        if (!$field->access('create')) {
          throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
        }
      }

      // Validate the received data before saving.
      $this->validate($entity);
      try {
        $entity->save();
        $rev = $entity->_revs_info->rev;
        return new ResourceResponse(array('ok' => TRUE, 'id' => $entity->uuid(), 'rev' => $rev), 201, array('ETag' => $rev));
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, NULL, $e);
      }
    }
  }
}