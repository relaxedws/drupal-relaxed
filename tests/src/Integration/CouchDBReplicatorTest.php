<?php

namespace Drupal\Tests\relaxed\Integration;

/**
 * @group relaxed
 */
class CouchDBReplicatorTest extends ReplicationTestBase {

  /**
   * Test the replication using the CouchDB replicator.
   */
  public function testCouchdbReplicator() {
    // Run CouchDB to Drupal replication.
    $this->couchDbReplicate($this->couchdb_url . "/$this->source_db", 'http://replicator:replicator@localhost:8080/relaxed/live');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/live/_all_docs', 9);

    // Run Drupal to Drupal replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8080/relaxed/live', 'http://replicator:replicator@localhost:8081/relaxed/live');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/live/_all_docs', 9);

    // Run Drupal to CouchDB replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8081/relaxed/live', $this->couchdb_url . "/$this->target_db");
    $this->assertAllDocsNumber($this->couchdb_url . "/$this->target_db/_all_docs", 9);

    // Delete source database.
    $response_code = $this->deleteDb($this->source_db);
    $this->assertEquals(200, $response_code);

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);
  }

}
