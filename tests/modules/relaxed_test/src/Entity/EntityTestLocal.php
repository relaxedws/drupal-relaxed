<?php

/**
 * @file
 * Contains \Drupal\relaxed_test\Entity\EntityTestLocal.
 */

namespace Drupal\relaxed_test\Entity;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_local",
 *   label = @Translation("Test entity - local"),
 *   base_table = "entity_test_local",
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name"
 *   },
 *   local = TRUE,
 * )
 */
class EntityTestLocal extends EntityTestRelaxed { }
