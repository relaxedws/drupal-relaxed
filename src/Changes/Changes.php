<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\Index\SequenceIndex;

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

    // Format the result array.
    $result = array();
    foreach ($changes as $seq => $change) {
      if (!empty($change['local'])) {
        continue;
      }

      $uuid = $change['entity_uuid'];
      if (isset($result['results'][$uuid])) {
        unset($result['results'][$uuid]);
      }

      // Add the more recent change to the result array.
      $result['results'][$uuid] = array(
        'changes' => array(
          array('rev' => $change['rev']),
        ),
        'id' => $uuid,
        'seq' => $seq,
      );
      $result['last_seq'] = $seq;
      if ($change['deleted']) {
        $result['results'][$uuid]['deleted'] = TRUE;
      }
    }
    $result['results'] = array_values($result['results']);

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
