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
    sleep(30);

    // Run Drupal to Drupal replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8080/relaxed/default', 'http://replicator:replicator@localhost:8081/relaxed/default');
    sleep(30);

    // Run Drupal to CouchDB replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8081/relaxed/default', $this->couchdb_url . "/$this->target_db");
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
