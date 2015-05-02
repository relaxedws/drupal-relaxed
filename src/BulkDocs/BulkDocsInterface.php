<?php

namespace Drupal\relaxed\BulkDocs;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface BulkDocsInterface {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  static public function createInstance(ContainerInterface $container, WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace);

  /**
   * @param boolean $new_edits
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function newEdits($new_edits);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function setEntities($entities);

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  public function getEntities();

  /**
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function save();

  /**
   * @return array
   */
  public function getResult();

}
