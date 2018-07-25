<?php

namespace Drupal\relaxed;

use Drupal\workspaces\WorkspaceInterface;

interface RevisionDiffFactoryInterface {

  /**
   * Constructs a new RevisionDiff instance.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *
   * @return \Drupal\relaxed\RevisionDiff\RevisionDiff
   */
  public function get(WorkspaceInterface $workspace);

}
