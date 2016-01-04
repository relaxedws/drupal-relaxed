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
    sleep(30);

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}');
    sleep(30);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"dbname": "' . $this->target_db . '"}}');
    sleep(30);

    // Get all docs from CouchDB target db.
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_URL => "$this->couchdb_url/$this->target_db/_all_docs",
    ]);
    $response = curl_exec($curl);
    $this->assertContains('"total_rows":14', $response, 'The request returned the correct number of docs.');
    curl_close($curl);

    // Delete source database.
    $response_code = $this->deleteDb($this->source_db);
    $this->assertEquals(200, $response_code);

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);
  }

}
