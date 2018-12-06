<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Workspace entity normalizer and denormalizer.
 */
class WorkspaceNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = [WorkspaceInterface::class];

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
    $id = $entity->id();
    $return_data['db_name'] = $id;

    $return_data['update_seq'] = (int) \Drupal::service('multiversion.entity_index.sequence')->useWorkspace($id)->getLastSequenceId();

    if ($created = (string) $entity->created->value) {
      $return_data['instance_start_time'] = $created;
    }

    return $return_data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (isset($data['db_name'])) {
      $data['id'] = $data['db_name'];
      unset($data['db_name']);
    }
    if (isset($data['instance_start_time'])) {
      $data['created'] = $data['instance_start_time'];
      unset($data['instance_start_time']);
    }
    return $this->entityTypeManager->getStorage('workspace')->create($data);
  }
}
