<?php

/**
 * @file
 * Contains \Drupal\relaxed\StubEntityProcessor\StubEntityProcessor.
 */

namespace Drupal\relaxed\StubEntityProcessor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

class StubEntityProcessor implements StubEntityProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processEntity(ContentEntityInterface $entity) {
    // Check if the entity is a stub entity and exists, if it already exists,
    // update it if needed.
    $controller = \Drupal::entityManager()
      ->getStorage($entity->getEntityTypeId());
    $existing_entities = $controller
      ->loadByProperties(['uuid' => $entity->uuid()]);
    $entity_by_uuid = reset($existing_entities);

    if ($entity_by_uuid && !$entity->id()) {
      $entity = $this->updateStubEntity($entity, $entity_by_uuid);
      $entity->_rev->is_stub = TRUE;
    }

    // Save stub entities for entity reference fields.
    $this->saveStubEntities($entity);

    return $entity;
  }

  /**
   * Saves stub entities and update the target entity.
   */
  protected function saveStubEntities(ContentEntityInterface $entity) {
    foreach ($entity as $field_name => $field) {
      // For entity reference fields we should check if the referenced entity
      // exists or we should save a stub entity.
      if ($field instanceof EntityReferenceFieldItemListInterface) {
        foreach ($field as $delta => $item) {
          // Create a stub entity for entity reference field if
          // it doesn't exist.
          if (isset($item->entity_to_save)) {
            $entity_to_save = $item->entity_to_save;
            $controller = \Drupal::entityManager()
              ->getStorage($entity_to_save->getEntityTypeId());
            $existing_entities = $controller
              ->loadByProperties(['uuid' => $entity_to_save->uuid()]);
            $existing_entity = reset($existing_entities);
            // Unset information about the entity_to_save.
            unset($entity->{$field_name}[$delta]->entity_to_save);
            // If the entity already exists, don't save the stub entity, just
            // complete the field with the correct target_id.
            if ($existing_entity) {
              $entity->{$field_name}[$delta] = ['target_id' => $existing_entity->id()];
            }
            else {
              $entity_to_save->_rev->new_edit = FALSE;
              $entity_type = $entity_to_save->getEntityType();
              $id_key = $entity_type->getKey('id');
              if ($entity_to_save->{$id_key} && $entity_to_save->{$id_key}->value) {
                unset($entity_to_save->{$id_key}->value);
              }
              // Save the stub entity and set the target_id value to the field item.
              $entity_to_save->save();
              $entity->{$field_name}[$delta] = ['target_id' => $entity_to_save->id()];
            }
          }
        }
      }
    }
  }

  /**
   * Updates a stub entity.
   */
  protected function updateStubEntity(ContentEntityInterface $entity, ContentEntityInterface $existing_entity) {
    $id_key = $entity->getEntityType()->getKey('id');
    $revision_key = $entity->getEntityType()->getKey('revision');
    $exclude = [$id_key, $revision_key, 'uuid', '_rev'];
    foreach ($existing_entity as $field_name => $field) {
      if (!in_array($field_name, $exclude) && $entity->{$field_name}->value) {
        $existing_entity->{$field_name}->value = $entity->{$field_name}->value;
      }
    }
    return $existing_entity;
  }

}
