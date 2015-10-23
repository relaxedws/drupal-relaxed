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
  static public function createInstance(ContainerInterface $container, SequenceIndexInterface $sequence_index, WorkspaceInterface $workspace) {
    return new static(
      $sequence_index,
      $workspace,
      $container->get('entity.manager'),
      $container->get('serializer')
    );
  }

  /**
   * @param \Drupal\multiversion\Entity\Index\SequenceIndex $sequenceIndex
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   */
  public function __construct(SequenceIndex $sequenceIndex, WorkspaceInterface $workspace, EntityManagerInterface $entity_manager, SerializerInterface $serializer) {
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
  public function getNormal() {
    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSeq, NULL);

    // Format the result array.
    $changes = array();
    foreach ($sequences as $sequence) {
      if (!empty($sequence['local']) || strpos($sequence['rev'], '1-101010101010101010101010') !== FALSE) {
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
   *
   * @todo {@link https://www.drupal.org/node/2599908 Implement this longpoll functionality fully.}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceId)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    // Format longpoll change to API spec.
    return $change;
  }

}
