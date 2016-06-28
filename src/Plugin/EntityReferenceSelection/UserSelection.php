<?php

namespace Drupal\relaxed\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection as CoreUserSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "relaxed:user",
 *   label = @Translation("Relaxed user selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 20
 * )
 */
class UserSelection extends CoreUserSelection {

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $user = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // Since the user entity type doesn't have a label property defined (which
    // is strange by itself) we set it here, since label is the logical input
    // for the name.
    $user->name->value = $label;

    return $user;
  }

}
