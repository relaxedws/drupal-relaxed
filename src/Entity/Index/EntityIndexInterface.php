<?php

namespace Drupal\relaxed\Entity\Index;

use Drupal\Core\Entity\EntityInterface;

interface EntityIndexInterface extends IndexInterface {

  /**
   * @param string $key
   *
   * @return array
   */
  public function get($key);

  /**
   * @param array $keys
   *
   * @return array
   */
  public function getMultiple(array $keys);

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function add(EntityInterface $entity);

  /**
   * @param array $entities
   */
  public function addMultiple(array $entities);

}
