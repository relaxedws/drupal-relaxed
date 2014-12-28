<?php

/**
 * @file
 * Contains \Drupal\relaxed\RevisionDiff\RevisionDiff.
 */

namespace Drupal\relaxed\RevisionDiff;

use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RevisionDiff implements RevisionDiffInterface {

  /**
   * @var array
   */
  protected $revisionIds = array();

  /**
   * {@inheritdoc}
   */
  static public function createInstance(ContainerInterface $container, RevisionIndexInterface $rev_index, WorkspaceInterface $workspace) {
    return new static(
      $rev_index,
      $workspace
    );
  }

  /**
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   */
  public function __construct(RevisionIndexInterface $rev_index, WorkspaceInterface $workspace) {
    $this->revisionIndex = $rev_index;
    $this->workspaceId = $workspace->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionIds(array $revision_ids) {
    $this->revisionIds = $revision_ids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionIds() {
    return $this->revisionIds;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Move any assumptions about the serialization format into the
   *   serializer to better separate concerns.
   */
  public function getMissing() {
    $keys = array();
    foreach ($this->getRevisionIds() as $entity_uuid => $revision_ids) {
      foreach ($revision_ids as $revision_id) {
        $keys[] = $entity_uuid . ':' . $revision_id;
      }
    }
    $existing_revision_ids = $this->revisionIndex->getMultiple($keys);

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
