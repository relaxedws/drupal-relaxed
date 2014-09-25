<?php

/**
 * @file
 * Contains \Drupal\relaxed\RevisionDiff\RevisionDiff.
 */

namespace Drupal\relaxed\RevisionDiff;

use Drupal\multiversion\Entity\RevisionIndex;

class RevisionDiff implements RevisionDiffInterface {

  public $entityKeys = array();

  public function __construct(RevisionIndex $rev_index) {
    $this->revisionIndex = $rev_index;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityKeys(array $entity_keys) {
    $this->entityKeys = $entity_keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityKeys() {
    return $this->entityKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function getMissing() {
    $keys = array();
    $entity_keys = $this->getEntityKeys();
    foreach ($entity_keys as $entity_uuid => $revision_ids) {
      foreach ($revision_ids as $revision_id) {
        $keys[] = $entity_uuid . ':' . $revision_id;
      }
    }
    $existing_revision_ids = $this->revisionIndex->getMultiple($keys);

    // Do diff.
    $missing_revision_ids = array();
    foreach ($keys as $key) {
      if (!isset($existing_revision_ids[$key])) {
        $uuid = substr($key, 0, strpos($key, ':'));
        $missing_revision_ids[$uuid]['missing'][] = substr($key, strpos($key, ':') + 1);
      }
    }

    return $missing_revision_ids;
  }

}
