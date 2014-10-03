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
      ->useWorkspace($this->workspaceName);

    $changes = $this->sequenceIndex
      ->useWorkspace($this->workspaceName)
      ->getRange($this->lastSeq, NULL);

    $id = 0;
    $entities = array();
    // Create an array with entities.
    foreach ($changes as $key => $change) {
      if ($id == 0) {
        $id = $change['entity_id'];
      }
      elseif ($change['entity_id'] == $id) {
        continue;
      }
      $id = $change['entity_id'];
      $entities[$id] = entity_load($change['entity_type'], $id);
    }

    $result = array();
    $last_seq_number = count($changes);
    if ($last_seq_number > 0) {
      $last_seq_number = $last_seq_number - 1;
      $result['last_seq'] = $changes[$last_seq_number]['local_seq'];
    }


    // Format the result array.
    foreach ($changes as $change) {
      $entity = $entities[$change['entity_id']];
      $id = $entity->uuid();

      $revs = array();
      $revs_count = $entity->_revs_info->count();
      if ($revs_count > 0) {
        $rev_number = 1;
        $id = $entity->uuid();
        while ($rev_number <= $revs_count) {
          if ($rev = $entity->_revs_info->get($rev_number)->rev) {
            $revs[] = array(
              'rev' => $rev,
            );
          }
          $rev_number++;
        }
      }

      $result['results'][] = array(
        'changes' => $revs,
        'deleted' => $change['deleted'],
        'id' => $id,
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
