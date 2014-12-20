<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\Index\SequenceIndex;
use Drupal\multiversion\Entity\Index\SequenceIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Changes implements ChangesInterface {

  /**
   * @var string
   */
  protected $workspaceId;

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
      $workspace
    );
  }

  /**
   * @param \Drupal\multiversion\Entity\Index\SequenceIndex $sequenceIndex
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   */
  public function __construct(SequenceIndex $sequenceIndex, WorkspaceInterface $workspace) {
    $this->sequenceIndex = $sequenceIndex;
    $this->workspaceId = $workspace->id();
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
    foreach ($sequences as $seq => $sequence) {
      if (!empty($sequence['local'])) {
        continue;
      }

      $uuid = $sequence['entity_uuid'];
      $changes[$uuid] = array(
        'changes' => array(
          array('rev' => $sequence['rev']),
        ),
        'id' => $uuid,
        'seq' => $seq,
      );
      if ($sequence['deleted']) {
        $changes[$uuid]['deleted'] = TRUE;
      }
    }
    return array_values($changes);
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      // @todo Implement exponential wait time.
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceId)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    // Format longpoll change to API spec.
    return $change;
  }

}
