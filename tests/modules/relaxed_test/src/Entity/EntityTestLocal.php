<?php

namespace Drupal\relaxed_test\Entity;

use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_local",
 *   label = @Translation("Test entity - local"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *   },
 *   base_table = "entity_test_local",
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   local = TRUE,
 * )
 */
class EntityTestLocal extends EntityTestRev { }
