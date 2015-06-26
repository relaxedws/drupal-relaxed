<?php

/**
 * @file
 * Contains \Drupal\relaxed\StubEntityProcessor\StubEntityProcessorInterface.
 */

namespace Drupal\relaxed\StubEntityProcessor;

use Drupal\Core\Entity\ContentEntityInterface;

interface StubEntityProcessorInterface {

  /**
   * Processes an entity and saves stub entities, for entity reference fields,
   * when the referenced entity does not exist. If the processed entity has an
   * stub entity then update the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function processEntity(ContentEntityInterface $entity);

}
