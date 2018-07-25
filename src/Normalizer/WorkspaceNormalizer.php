<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceType;
use Drupal\multiversion\Entity\WorkspaceTypeInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Workspace entity normalizer and denormalizer.
 */
class WorkspaceNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = ['Drupal\workspaces\WorkspaceInterface'];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $context['entity_type'] = 'workspace';

    $return_data = [];
    if ($machine_name = (string) $entity->getMachineName()) {
      $return_data['db_name'] = $machine_name;
    }

    if ($update_seq = $entity->getUpdateSeq()) {
      $return_data['update_seq'] = (int) $update_seq;
    }
    else {
      // Replicator expects update_seq to be always set.
      $return_data['update_seq'] = 0;
    }

    if ($created = (string) $entity->getStartTime()) {
      $return_data['instance_start_time'] = $created;
    }

    return $return_data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (isset($data['db_name'])) {
      $data['machine_name'] = $data['db_name'];
      unset($data['db_name']);
    }
    if (isset($data['instance_start_time'])) {
      $data['created'] = $data['instance_start_time'];
      unset($data['instance_start_time']);
    }
    $workspace_types = WorkspaceType::loadMultiple();
    $workspace_type = reset($workspace_types);
    if (!($workspace_type instanceof WorkspaceTypeInterface)) {
      throw new \Exception('Invalid workspace type.');
    }
    $data['type'] = $workspace_type->id();
    return $this->entityTypeManager->getStorage('workspace')->create($data);
  }
}
