<?php

namespace Drupal\Tests\relaxed\Integration;

require_once __DIR__ . '/ReplicationTestBase.php';

/**
 * @group relaxed
 */
class ReplicateEndpointTest extends ReplicationTestBase {

  /**
   * Test the /_replicate endpoint.
   */
  public function testReplicateEndpoint() {
    // Run CouchDB to Drupal replication.
    $this->endpointReplicate('{"source": {"dbname": "' . $this->sourceDb . '", "port": ' . $this->port . '}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/live/_all_docs', 9);

    // Run Drupal to Drupal replication.
    $this->endpointReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/live/_all_docs', 9);

    // Run Drupal to CouchDB replication.
    $this->endpointReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}, "target": {"dbname": "' . $this->targetDb . '", "port": ' . $this->port . '}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 9);
  }

}
