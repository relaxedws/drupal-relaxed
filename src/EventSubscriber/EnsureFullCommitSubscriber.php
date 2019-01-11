<?php

namespace Drupal\relaxed\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\relaxed\Event\RelaxedEnsureFullCommitEvent;
use Drupal\relaxed\Event\RelaxedEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EnsureFullCommitResourceSubscriber.
 *
 * @package Drupal\relaxed\EventSubscriber
 */
class EnsureFullCommitSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AliasType manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * The Pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathAutoGenerator;

  /**
   * The Workspace manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * EnsureFullCommitResourceSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   The AliasType manager.
   * @param \Drupal\pathauto\PathautoGeneratorInterface $path_auto_generator
   *   The Pathauto generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, AliasTypeManager $alias_type_manager = NULL, PathautoGeneratorInterface $path_auto_generator = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->aliasTypeManager = $alias_type_manager;
    $this->pathAutoGenerator = $path_auto_generator;
  }

  /**
   * Listener for ensures the replication is complete.
   *
   * @param \Drupal\relaxed\Event\RelaxedEnsureFullCommitEvent $event
   *   \Drupal\relaxed\Event\RelaxedEnsureFullCommitEvent.
   */
  public function onEnsureFullCommit(RelaxedEnsureFullCommitEvent $event) {
    if ($this->aliasTypeManager && $this->pathAutoGenerator) {
      $workspace = $event->getWorkspace();
      $current_workspace = $this->workspaceManager->getActiveWorkspace();

      $this->workspaceManager->setActiveWorkspace($workspace);

      $definitions = $this->aliasTypeManager->getVisibleDefinitions();
      foreach ($definitions as $definition) {
        foreach ($definition['context'] as $entity_type => $context_definition) {
          $this->updateEntitiesAlias($entity_type);
        }
      }

      $this->workspaceManager->setActiveWorkspace($current_workspace);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RelaxedEvents::REPLICATION_ENSURE_FULL_COMMIT][] = ['onEnsureFullCommit'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  private function updateEntitiesAlias($entity_type_id, $limit = 50) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($storage instanceof ContentEntityStorageInterface) {
      $query = $storage->getQuery()
        ->condition('_deleted', 0);
      if ($entity_type_id === 'node') {
        $query->condition('status', 1);
      }
      $ids = $query->execute();

      foreach (array_chunk($ids, $limit) as $ids_subset) {
        $entities = $storage->loadMultiple($ids_subset);
        foreach ($entities as $entity) {
          // Update aliases for the entity's default language
          // and its translations.
          foreach ($entity->getTranslationLanguages() as $langcode => $language) {
            $translated_entity = $entity->getTranslation($langcode);
            if ($this->pathAutoGenerator->updateEntityAlias($translated_entity, 'insert')) {
              \Drupal::logger('replication')->info('Entity %entity_type(%id) alias update', ['%entity_type' => $entity_type_id, '%id' => $entity->id()]);
            }
          }
        }
      }
    }
  }

}
