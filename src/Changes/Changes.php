<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\Sequence\SequenceFactory;

class Changes implements ChangesInterface {

  protected $lastSeq = 0;

  function __construct(SequenceFactory $sequence_factory) {
    $this->sequenceFactory = $sequence_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function workspace($name) {
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
    $changes = $this->sequenceFactory
      ->workspace($this->workspaceName);

    $changes = $this->sequenceFactory
      ->workspace($this->workspaceName)
      ->getRange($this->lastSeq, NULL);
    // Format the changes to API spec.
    return $changes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      // @todo Implement exponential wait time.
      $change = $this->sequenceFactory
        ->workspace($this->workspaceName)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    // Format longpoll change to API spec.
    return $change;
  }

}
