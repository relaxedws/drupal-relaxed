<?php

/**
 * @file
 * Contains \Drupal\relaxed\RevisionDiff\RevisionDiffInterface.
 */

namespace Drupal\relaxed\RevisionDiff;

interface RevisionDiffInterface {

  /**
   * @param array $entity_keys
   * @return array
   */
  public function entities(array $entity_keys);

  /**
   * Returns missing revisions ids.
   * @return array
   */
  public function getMissing();

}
