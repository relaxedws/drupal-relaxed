<?php

namespace Drupal\relaxed\Event;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a replication finished event for event listeners.
 */
class RelaxedEnsureFullCommitEvent extends Event {

  /**
   * The target workspace during the replication.
   *
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * Constructs a RelaxedEnsureFullCommitEvent event object.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The target workspace during the replication.
   */
  public function __construct(WorkspaceInterface $workspace) {
    $this->workspace = $workspace;
  }

  /**
   * Gets the workspace.
   *
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   *   Return workspace object.
   */
  public function getWorkspace() {
    return $this->workspace;
  }

}
