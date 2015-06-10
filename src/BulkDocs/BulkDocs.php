<?php

namespace Drupal\relaxed\BulkDocs;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BulkDocs implements BulkDocsInterface {

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
  static public function createInstance(ContainerInterface $container, WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace) {
    return new static(
      $workspace_manager,
      $workspace
    );
  }

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace) {
    $this->workspaceManager = $workspace_manager;
    $this->workspace = $workspace;
  }

  /**
   * {@inheritdoc}
   */
  public function newEdits($new_edits) {
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
   */
  public function save() {
    $inital_workspace = $this->workspaceManager->getActiveWorkspace();
    $this->workspaceManager->setActiveWorkspace($this->workspace);

    foreach ($this->entities as $entity) {
      try {
        $entity->_rev->new_edit = $this->newEdits;

        // This will save stub entities in case the entity has entity reference
        // fields and a referenced entity does not exist or will update stub
        // entities with the correct values.
        \Drupal::service('relaxed.stub_entity_processor')->processEntity($entity);

        $entity->save();

        $this->result[] = array(
          'ok' => TRUE,
          'id' => $entity->uuid(),
          'rev' => $entity->_rev->value,
        );
      }
      catch (\Exception $e) {
        $this->result[] = array(
          'error' => $e->getMessage(),
          'reason' => 'exception',
          'id' => $entity->uuid(),
          'rev' => $entity->_rev->value,
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
