<?php

namespace Drupal\Tests\relaxed\Integration;

/**
 * @group relaxed
 */
class ReplicateEndpointTest extends ReplicationTestBase {

  /**
   * Test the /_replicate endpoint.
   */
  public function testReplicateEndpoint() {
    // Run CouchDB to Drupal replication.
    $this->endpointReplicate('{"source": {"dbname": "' . $this->source_db . '"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/live/_all_docs', 9);

    // Run Drupal to Drupal replication.
    $this->endpointReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/live/_all_docs', 9);

    // Run Drupal to CouchDB replication.
    $this->endpointReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}, "target": {"dbname": "' . $this->target_db . '"}}', 'http://replicator:replicator@localhost:8080/relaxed/_replicate');
    $this->assertAllDocsNumber($this->couchdb_url . "/$this->target_db/_all_docs", 9);

    // Delete source database.
    $response_code = $this->deleteDb($this->source_db);
    $this->assertEquals(200, $response_code);

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);
  }

}
