<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\SequenceIndex;

class Changes implements ChangesInterface {

  protected $lastSeq = 0;

  function __construct(SequenceIndex $sequenceIndex) {
    $this->sequenceIndex = $sequenceIndex;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($name) {
    $this->workspaceName = $name;
    return $this;
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
    $changes = $this->sequenceIndex
      ->useWorkspace($this->workspaceName)
      ->getRange($this->lastSeq, NULL);

    $result = array();
    $last_seq_number = count($changes);
    if ($last_seq_number > 0) {
      $last_seq_number = $last_seq_number - 1;
      $result['last_seq'] = $changes[$last_seq_number]['local_seq'];
    }

    // Format the result array.
    foreach ($changes as $change) {
      $result['results'][] = array(
        'changes' => array(
          'rev' => $change['rev'],
        ),
        'deleted' => $change['deleted'],
        'id' => $change['entity_id'],
        'seq' => $change['local_seq'],
      );
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      // @todo Implement exponential wait time.
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceName)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    // Format longpoll change to API spec.
    return $change;
  }

}
