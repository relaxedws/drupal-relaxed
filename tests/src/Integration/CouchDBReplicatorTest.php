<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Integration\CouchDBReplicatorTest.
 */

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
    $this->couchDbReplicate($this->couchdb_url . "/$this->source_db", 'http://replicator:replicator@localhost:8080/relaxed/default');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/default/_all_docs', 11);

    // Run Drupal to Drupal replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8080/relaxed/default', 'http://replicator:replicator@localhost:8081/relaxed/default');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/default/_all_docs', 14);

    // Run Drupal to CouchDB replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8081/relaxed/default', $this->couchdb_url . "/$this->target_db");
    $this->assertAllDocsNumber($this->couchdb_url . "/$this->target_db/_all_docs", 14);

    // Delete source database.
    $response_code = $this->deleteDb($this->source_db);
    $this->assertEquals(200, $response_code);

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);
  }

}
