<?php

namespace Drupal\relaxed;

use Drupal\workspaces\WorkspaceInterface;

interface ChangesFactoryInterface {

  /**
   * Constructs a new Changes instance.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *
   * @return \Drupal\relaxed\Changes\ChangesInterface
   */
  public function get(WorkspaceInterface $workspace);

}
