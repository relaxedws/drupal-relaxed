<?php

/**
 * @file
 * Hooks provided by the RELAXed Web Services module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Returns an array of values specific for an entity type.
 *
 * These values will be used when creating stub entities for entity reference
 * fields.
 *
 * @param \Drupal\Core\Entity\EntityStorageInterface $target_storage
 *
 * @return array
 *
 * @ingroup relaxed_api
 */
function hook_entity_create_stub(EntityStorageInterface $target_storage) {
  $target_entity_values = array();

  if ($target_storage instanceof TermStorageInterface) {
    $target_entity_values['vid'] = 'tags';
    $target_entity_values['name'] = 'Stub name for taxonomy term';
  }

  return $target_entity_values;
}

/**
 * @} End of "addtogroup hooks".
 */
