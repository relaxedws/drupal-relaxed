<?php

namespace Drupal\relaxed\Event;

/**
 * Relaxed events.
 */
final class RelaxedEvents {

  /**
   * Event fired when replication ends/fails and a replication log is created.
   *
   * This event allows modules to perform an action whenever a replication ends
   * with success or fails. The event listener method
   *  receives a \Drupal\relaxed\Event\RelaxedReplicationFinishedEvent instance.
   */
  const REPLICATION_FINISHED = 'relaxed.replication_finished';

  /**
   * Event fired when replication handles the Ensure Full Commit request.
   */
  const REPLICATION_ENSURE_FULL_COMMIT = 'relaxed.replication_ensure_full_commit';

}
