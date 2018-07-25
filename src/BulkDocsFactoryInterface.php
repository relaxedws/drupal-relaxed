<?php

namespace Drupal\relaxed;

use Drupal\workspaces\WorkspaceInterface;

interface BulkDocsFactoryInterface {

  /**
   * Constructs a new BulkDocs instance.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function get(WorkspaceInterface $workspace);

}
