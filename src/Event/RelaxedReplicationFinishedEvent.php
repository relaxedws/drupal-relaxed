<?php

namespace Drupal\relaxed\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a replication finished event for event listeners.
 */
class RelaxedReplicationFinishedEvent extends Event {

  /**
   * The replication log returned by replication.
   *
   * @var array
   */
  protected $replicationInfo;

  /**
   * Constructs a RelaxedReplicationFinished event object.
   *
   * @param $replication_info
   */
  public function __construct($replication_info) {
    $this->replicationInfo = $replication_info;
  }

  /**
   * Gets the replication info.
   *
   * @return array
   */
  public function getReplicationInfo() {
    return $this->replicationInfo;
  }

}
