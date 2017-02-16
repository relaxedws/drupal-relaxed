<?php

namespace Drupal\relaxed\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

class RevisionIndex extends EntityIndex implements RevisionIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'multiversion.entity_index.rev.';

  /**
   * {@inheritdoc}
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->uuid() . ':' . $entity->_rev->value;
  }

}
