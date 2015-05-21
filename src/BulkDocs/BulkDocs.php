<?php

namespace Drupal\relaxed\BulkDocs;

use Drupal\multiversion\Entity\Transaction\TransactionInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BulkDocs implements BulkDocsInterface {

  /**
   * @var \Drupal\multiversion\Entity\Transaction\TransactionInterface
   */
  protected $trx;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities = array();

  /**
   * @var bool
   */
  protected $newEdits = TRUE;

  /**
   * @var array
   */
  protected $result = array();

  /**
   * {@inheritdoc}
   */
  static public function createInstance(ContainerInterface $container, TransactionInterface $trx, WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace) {
    return new static(
      $trx,
      $workspace_manager,
      $workspace
    );
  }

  /**
   * Construcs a BuldDocs object.
   *
   * @param \Drupal\multiversion\Entity\Transaction\TransactionInterface $trx
   *   The transaction instance.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager instance.
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace instance
   */
  public function __construct(TransactionInterface $trx, WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace) {
    $this->trx = $trx;
    $this->workspaceManager = $workspace_manager;
    $this->workspace = $workspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewEdits($new_edits) {
    $this->newEdits = (bool) $new_edits;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntities($entities) {
    $this->entities = $entities;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities() {
    return $this->entities;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use self::$trx when transactions can handle multiple entity types.
   */
  public function save() {
    $inital_workspace = $this->workspaceManager->getActiveWorkspace();
    $this->workspaceManager->setActiveWorkspace($this->workspace);

    foreach ($this->entities as $entity) {
      try {
        $entity->new_edits = $this->newEdits;
        if (!$entity->isNew()) {
          // Ensure that deleted entities will be saved just once.
          $id = $entity->id();
          if ($id) {
            $deleted_entity = entity_load_deleted($entity->getEntityTypeId(), $id, TRUE);
          }
        }
        if (empty($deleted_entity)) {
          $entity->save();
        }

        $this->result[] = array(
          'ok' => TRUE,
          'id' => $entity->uuid(),
          'rev' => $entity->_revs_info->rev,
        );
      }
      catch (\Exception $e) {
        $this->result[] = array(
          'error' => $e->getMessage(),
          'reason' => 'exception',
          'id' => $entity->uuid(),
          'rev' => $entity->_revs_info->rev,
        );
        // @todo Inject logger or use \Drupal::logger().
        watchdog_exception('relaxed', $e);
      }
    }

    // Switch back to the initial workspace.
    $this->workspaceManager->setActiveWorkspace($inital_workspace);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return $this->result;
  }

}
