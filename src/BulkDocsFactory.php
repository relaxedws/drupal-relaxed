<?php

namespace Drupal\relaxed;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\relaxed\BulkDocs\BulkDocs;

class BulkDocsFactory implements BulkDocsFactoryInterface {

  /**
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndexInterface
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionIndexInterface
   */
  protected $revIndex;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\relaxed\BulkDocs\BulkDocs[]
   */
  protected $instances = [];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndexInterface $uuid_index
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, UuidIndexInterface $uuid_index, RevisionIndexInterface $rev_index, EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock, LoggerChannelFactoryInterface $logger_factory, StateInterface $state) {
    $this->workspaceManager = $workspace_manager;
    $this->uuidIndex = $uuid_index;
    $this->revIndex = $rev_index;
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
    $this->logger = $logger_factory->get('replication');
    $this->state = $state;
  }

  /**
   * @inheritDoc
   */
  public function get(WorkspaceInterface $workspace) {
    if (!isset($this->instances[$workspace->id()])) {
      $this->instances[$workspace->id()] = new BulkDocs(
        $this->workspaceManager,
        $workspace,
        $this->uuidIndex,
        $this->revIndex,
        $this->entityTypeManager,
        $this->lock,
        $this->logger,
        $this->state
      );
    }
    return $this->instances[$workspace->id()];
  }

}
