<?php

namespace Drupal\Tests\relaxed\Integration;

require_once __DIR__ . '/ReplicationTestBase.php';

/**
 * @group relaxed
 */
class CouchDBReplicatorTest extends ReplicationTestBase {

  /**
   * Test the replication using the CouchDB replicator.
   */
  public function testCouchdbReplicator() {
    // Run CouchDB to Drupal replication.
    $this->couchDbReplicate($this->couchdbUrl . '/' . $this->sourceDb, 'http://replicator:replicator@localhost:8080/relaxed/live');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/live/_all_docs', 9);

    // Run Drupal to Drupal replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8080/relaxed/live', 'http://replicator:replicator@localhost:8081/relaxed/live');
    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8081/relaxed/live/_all_docs', 9);

    // Run Drupal to CouchDB replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8081/relaxed/live', $this->couchdbUrl . '/' . $this->targetDb);
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 9);
  }

}
