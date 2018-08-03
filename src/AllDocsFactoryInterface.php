<?php

namespace Drupal\relaxed;

use Drupal\workspaces\WorkspaceInterface;

interface AllDocsFactoryInterface {

  /**
   * Constructs a new AllDocs instance.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function get(WorkspaceInterface $workspace);

}
