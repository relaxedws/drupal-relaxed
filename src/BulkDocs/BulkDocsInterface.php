<?php

namespace Drupal\relaxed\BulkDocs;

use Drupal\multiversion\Entity\Transaction\TransactionInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface BulkDocsInterface {

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\multiversion\Entity\Transaction\TransactionInterface $trx
   *   The transaction instance.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager instance.
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace instance
   *
   * @return static
   */
  static public function createInstance(ContainerInterface $container, TransactionInterface $trx, WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace);

  /**
   * Sets the new edits flag.
   *
   * @param boolean $new_edits
   *   The new edits flag to set.
   *
   * @return $this
   */
  public function setNewEdits($new_edits);

  /**
   * Sets the the entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of fully loaded content entities.
   *
   * @return $this
   */
  public function setEntities($entities);

  /**
   * Gets the enties.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of content entities.
   */
  public function getEntities();

  /**
   * Saves the enties on the bulk document.
   *
   * @return $this
   */
  public function save();

  /**
   * @return array
   */
  public function getResult();

}
