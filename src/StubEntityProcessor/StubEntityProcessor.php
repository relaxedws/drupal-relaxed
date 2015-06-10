<?php

/**
 * @file
 * Contains \Drupal\relaxed\StubEntityProcessor\StubEntityProcessor.
 */

namespace Drupal\relaxed\StubEntityProcessor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

class StubEntityProcessor {

  /**
   * Processes an entity and saves stub entities, for entity reference fields,
   * when the referenced entity does not exist. If the processed entity has an
   * stub entity then update the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function processEntity(ContentEntityInterface $entity) {
    foreach ($entity as $field_name => $field) {
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
    return $entity;
  }

}
