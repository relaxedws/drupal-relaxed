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

        foreach ($entity as $field_name => $field) {
          // For entity reference fields we should check if the referenced entity
          // exists or we should save a stub entity.
          if ($field instanceof EntityReferenceFieldItemListInterface) {
            foreach ($field as $delta => $item) {
              // Create a stub entity for entity reference field if
              // it doesn't exist.
              if (isset($item->entity_to_save)) {
                $entity_to_save = $item->entity_to_save;
                $existent_entities = entity_load_multiple_by_properties(
                  $item->entity_to_save->getEntityTypeId(),
                  array('uuid' => $item->entity_to_save->uuid())
                );
                $existent_entity = reset($existent_entities);
                // Unset information about the entity_to_save.
                unset($entity->{$field_name}[$delta]->entity_to_save);
                // If the entity already exists, don't save the stub entity, just
                // complete the field with the correct target_id.
                if ($existent_entity) {
                  $entity->{$field_name}[$delta] = array('target_id' => $existent_entity->id());
                  continue;
                }
                // Save the stub entity and set the target_id value to the field item.
                $entity_to_save->save();
                $entity->{$field_name}[$delta] = array('target_id' => $entity_to_save->id());
              }
            }
          }
        }

        $existent_entities = entity_load_multiple_by_properties($entity->getEntityTypeId(), array('uuid' => $entity->uuid()));
        $existent_entity = reset($existent_entities);
        // Update a stub entity with the correct values.
        if ($existent_entity && !$entity->id()) {
          $id_key = $entity->getEntityType()->getKey('id');
          foreach ($existent_entity as $field_name => $field) {
            if ($field_name != $id_key && $entity->{$field_name}->value) {
              $existent_entity->{$field_name}->value = $entity->{$field_name}->value;
            }
          }
          $entity = $existent_entity;
          $entity->isDefaultRevision(TRUE);
        }

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
