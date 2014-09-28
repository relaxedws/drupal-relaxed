<?php

namespace Drupal\relaxed\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

class ReplicationHistoryItemList extends FieldItemList {

  public function preSave() {
    // Generate the revision hash.
    $i = $this->isEmpty() ? 0 : $this->count();
    $entity = $this->getEntity();
    $rev = \Drupal::service('multiversion.manager')->newRevisionId($entity, $i);

    // Append the hash to our field.
    $this->get($i)->rev = $rev;

    // Reverse the item list to have the last revision first.
    $items = array_reverse($this->getValue());
    $this->setValue($items);

    parent::preSave();
  }

}
