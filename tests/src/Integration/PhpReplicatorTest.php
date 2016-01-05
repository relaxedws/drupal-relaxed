<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Integration\PhpReplicatorTest.
 */

namespace Drupal\Tests\relaxed\Integration;

/**
 * @group relaxed
 */
class PhpReplicatorTest extends ReplicationTestBase {

  /**
   * Test the replication using the PHP replicator.
   */
  public function testPhpReplicator() {
    // Run CouchDB to Drupal replication.
    $this->phpReplicate('{"source": {"dbname": "' . $this->source_db . '"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/default/_all_docs', 11);

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/default/_all_docs', 14);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"dbname": "' . $this->target_db . '"}}');
    $this->assertAllDocsNumber($this->couchdb_url . "/$this->target_db/_all_docs", 14);

    // Delete source database.
    $response_code = $this->deleteDb($this->source_db);
    $this->assertEquals(200, $response_code);

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);
  }

}
