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
   * @todo {@link https://www.drupal.org/node/2344005 Implement the
   *   possible_ancestors key.}
   */
  public function getMissing() {
    $missing = array();
    foreach ($this->getRevisionIds() as $uuid => $revision_ids) {
      $existing = $this->revisionIndex->getMultiple($revision_ids);
      foreach ($revision_ids as $revision_id) {
        if (!isset($existing[$revision_id])) {
          $missing[$uuid]['missing'][] = $revision_id;
        }
      }
    }
    return $missing;
  }

}
