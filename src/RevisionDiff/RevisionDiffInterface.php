<?php

/**
 * @file
 * Contains \Drupal\relaxed\RevisionDiff\RevisionDiffInterface.
 */

namespace Drupal\relaxed\RevisionDiff;

interface RevisionDiffInterface {

  /**
   * @param array $entity_keys
   */
  public function setEntityKeys(array $entity_keys);

  /**
   * @param array $entity_keys
   * @return array
   */
  public function getEntityKeys();

  /**
   * Returns missing revisions ids.
   * @return array
   */
  public function getMissing();

}
