<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\CouchDB\CouchDBClient;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;

  $json = json_decode($argv[1], true);
  if (json_last_error() != JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON.');
  }

  $source = CouchDBClient::create($json['source']);
  $target = CouchDBClient::create($json['target']);

  $task = new ReplicationTask();
  $replicator = new Replicator($source, $target, $task);

  $response = $replicator->startReplication();

?>