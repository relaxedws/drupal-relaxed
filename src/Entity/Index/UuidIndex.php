<?php

namespace Drupal\relaxed\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

class UuidIndex extends EntityIndex implements UuidIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'multiversion.entity_index.uuid.';

  /**
   * {@inheritdoc}
   */
  protected function buildKey(EntityInterface $entity) {
    return $entity->uuid();
  }

}
