<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
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
 *   id = "relaxed:db",
 *   label = "Workspace",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}",
 *   }
 * )
 */
class DbResource extends ResourceBase {

  /**
   * @param $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function head($entity) {
    if (!$entity instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }
    return new ResourceResponse(NULL, 200);
  }

  /**
   * @param $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($entity) {
    if (!$entity instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }
    // @todo: Access check.
    return new ResourceResponse($entity, 200);
  }

  /**
   * @param $name
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
   */
  public function put($name) {
    // If the name parameter was upcasted to an entity it means it an entity
    // already exists.
    if ($name instanceof WorkspaceInterface) {
      throw new PreconditionFailedHttpException(t('The database could not be created, it already exists'));
    }
    elseif (!is_string($name)) {
      throw new BadRequestHttpException(t('Database name is missing'));
    }

    try {
      // @todo Consider using the container injected in parent::create()
      $entity = \Drupal::service('entity.manager')
        ->getStorage('workspace')
        ->create(array('id' => $name))
        ->save();
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, t('Internal server error'), $e);
    }
    return new ResourceResponse(array('ok' => TRUE), 201);
  }

  /**
   * @param $workspace
   * @param ContentEntityInterface $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function post($workspace, ContentEntityInterface $entity = NULL) {
    // If the workspace parameter is a string it means it could not be upcasted
    // to an entity because none existed.
    if (is_string($workspace)) {
      throw new NotFoundHttpException(t('Database does not exist')); 
    }
    elseif (empty($entity)) {
      throw new BadRequestHttpException(t('No content received'));
    }
    $uuid = $entity->uuid();
    $rev = $entity->_rev->value;

    // Check for conflicts.
    /*if ($uuid) {
      $entry = \Drupal::service('entity.index.uuid')->get($uuid);
      if (!empty($entry)) {
        throw new ConflictHttpException();
      }
    }*/

    // Check entity and field level access.
    if (!$entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($entity as $field_name => $field) {
      if (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
      }

      // For entity reference fields we should check if the referenced entity
      // exists or we should save a stub entity.
      if ($field instanceof EntityReferenceFieldItemListInterface) {
        foreach ($field as $delta => $item) {
          // Create a stub entity for entity reference field if
          // it doesn't exist.
          if (isset($item->entity_to_save)) {
            $entity_to_save = $item->entity_to_save;
            $existent_entities = entity_load_multiple_by_properties(
              $item->entity_to_save->getEntityTypeId(),
              array('uuid' => $item->entity_to_save->uuid())
            );
            $existent_entity = reset($existent_entities);
            // Unset information about the entity_to_save.
            unset($entity->{$field_name}[$delta]->entity_to_save);
            // If the entity already exists, don't save the stub entity, just
            // complete the field with the correct target_id.
            if ($existent_entity) {
              $entity->{$field_name}[$delta] = array('target_id' => $existent_entity->id());
              continue;
            }
            // Save the stub entity and set the target_id value to the field item.
            $entity_to_save->save();
            $entity->{$field_name}[$delta] = array('target_id' => $entity_to_save->id());
          }
        }
      }
    }

    $existent_entities = entity_load_multiple_by_properties($entity->getEntityTypeId(), array('uuid' => $entity->uuid()));
    $existent_entity = reset($existent_entities);
    // Update a stub entity with the correct values.
    if ($existent_entity && !$entity->id()) {
      $id_key = $entity->getEntityType()->getKey('id');
      foreach ($existent_entity as $field_name => $field) {
        if ($field_name != $id_key && $entity->{$field_name}->value) {
          $existent_entity->{$field_name}->value = $entity->{$field_name}->value;
        }
      }
      $entity = $existent_entity;
      $entity->isDefaultRevision(TRUE);
    }

    // Validate the received data before saving.
    $this->validate($entity);
    try {
      $entity->save();
      return new ResourceResponse(array('ok' => TRUE, 'id' => $uuid, 'rev' => $rev), 201, array('ETag' => $rev));
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, NULL, $e);
    }
  }

  /**
   * @param WorkspaceInterface $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete(WorkspaceInterface $entity) {
    try {
      // @todo: Access check.
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new HttpException(500, NULL, $e);
    }
    return new ResourceResponse(array('ok' => TRUE), 200);
  }
}
