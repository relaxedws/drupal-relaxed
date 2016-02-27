<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\SequenceIndex;
use Drupal\multiversion\Entity\Index\SequenceIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * @todo {@link https://www.drupal.org/node/2282295 Implement remaining feed
 *   query types.}
 */
class Changes implements ChangesInterface {
  use DependencySerializationTrait;

  /**
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var boolean
   */
  protected $includeDocs = FALSE;

  /**
   * @var int
   */
  protected $lastSeq = 0;

  /**
   * {@inheritdoc}
   */
  static public function createInstance(ContainerInterface $container, WorkspaceInterface $workspace) {
    return new static(
      $workspace,
      $container->get('entity.index.sequence'),
      $container->get('entity.manager'),
      $container->get('serializer')
    );
  }

  /**
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\multiversion\Entity\Index\SequenceIndex $sequenceIndex
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   */
  public function __construct(WorkspaceInterface $workspace, SequenceIndex $sequenceIndex, EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
    $this->sequenceIndex = $sequenceIndex;
    $this->workspaceId = $workspace->id();
    $this->entityManager = $entity_manager;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function includeDocs($include_docs) {
    $this->includeDocs = $include_docs;
  }

  /**
   * {@inheritdoc}
   */
  public function lastSeq($seq) {
    $this->lastSeq = $seq;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanges() {
    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSeq, NULL, FALSE);

    // Format the result array.
    $changes = array();
    foreach ($sequences as $sequence) {
      if (!empty($sequence['local']) || !empty($sequence['is_stub'])) {
        continue;
      }

      $uuid = $sequence['entity_uuid'];
      $changes[$uuid] = array(
        'changes' => array(
          array('rev' => $sequence['rev']),
        ),
        'id' => $uuid,
        'seq' => $sequence['seq'],
      );
      if ($sequence['deleted']) {
        $changes[$uuid]['deleted'] = TRUE;
      }
      if ($this->includeDocs == TRUE) {
        /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
        $storage = $this->entityManager->getStorage($sequence['entity_type_id']);
        $revision = $storage->loadRevision($sequence['revision_id']);
        $changes[$uuid]['doc'] = $this->serializer->normalize($revision);
      }
    }

    // Now when we have rebuilt the result array we need to ensure that the
    // results array is still sorted on the sequence key, as in the index.
    $return = array_values($changes);
    usort($return, function($a, $b) {
      return $a['seq'] - $b['seq'];
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanged($seq) {
    return (bool) $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSeq, NULL, FALSE);
  }

}
