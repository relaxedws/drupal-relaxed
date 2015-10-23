<?php

namespace Drupal\relaxed\BulkDocs;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\relaxed\StubEntityProcessor\StubEntityProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class BulkDocs implements BulkDocsInterface {
  use DependencySerializationTrait;

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
      $workspace,
      $container->get('relaxed.stub_entity_processor')
    );
  }

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\relaxed\StubEntityProcessor\StubEntityProcessorInterface $stub_entity_processor
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, WorkspaceInterface $workspace, StubEntityProcessorInterface $stub_entity_processor) {
    $this->workspaceManager = $workspace_manager;
    $this->workspace = $workspace;
    $this->stubEntityProcessor = $stub_entity_processor;
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
        $entity = $this->stubEntityProcessor->processEntity($entity);

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
        // @todo {@link https://www.drupal.org/node/2599902 Inject logger or use \Drupal::logger().}
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
