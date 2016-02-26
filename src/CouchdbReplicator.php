<?php

namespace Drupal\relaxed;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\workspace\PointerInterface;
use Drupal\workspace\ReplicatorInterface;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replication;


class CouchdbReplicator implements ReplicatorInterface{

  /**
   * @inheritDoc
   */
  public function applies(PointerInterface $source, PointerInterface $target) {
    // TODO: Implement applies() method.
  }

  /**
   * @inheritDoc
   */
  public function replicate(PointerInterface $source, PointerInterface $target) {
    CouchDBClient::create();
    try {
      // Create the replication task.
      $task = new ReplicationTask();
      // Create the replication.
      $replication = new Replication($source, $target, $task);
      // Generate and set a replication ID.
      $replication->task->setRepId($replication->generateReplicationId());
      // Filter by document IDs if set.
      if (!empty($this->docIds)) {
        $replication->task->setDocIds($this->docIds);
      }
      // Start the replication.
      $replicationResult = $replication->start();
    }
    catch (\Exception $e) {
      \Drupal::logger('Deploy')->info($e->getMessage() . ': ' . $e->getTraceAsString());
      return ['error' => $e->getMessage()];
    }
    // Return the response.
    return $replicationResult;
  }

}