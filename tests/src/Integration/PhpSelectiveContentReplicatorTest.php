<?php

namespace Drupal\Tests\relaxed\Integration;

use Relaxed\Replicator\ReplicationTask;

require_once __DIR__ . '/ReplicationTestBase.php';

/**
 * @group relaxed
 */
class PhpSelectiveContentReplicatorTest extends ReplicationTestBase {

  /**
   * Test the selective content replication using the PHP replicator.
   */
  public function testPhpSelectiveContentReplication() {
    $source_workspace = 'source';
    $target_workspace = 'target';
    $this->createWorkspace('http://replicator:replicator@localhost:8080/relaxed', $source_workspace);
    $this->createWorkspace('http://replicator:replicator@localhost:8081/relaxed', $target_workspace);

    // Run CouchDB to Drupal replication. Replicate all 9 docs (entities).
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8080/relaxed/$source_workspace/_all_docs", 0);
    $this->phpReplicate('{"source": {"dbname": "' . $this->sourceDb . '", "port": ' . $this->port . '}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "' . $source_workspace . '", "timeout": 60}}');
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8080/relaxed/$source_workspace/_all_docs", 9);

    $task = new ReplicationTask();

    // Replicate only 2 documents (2 taxonomy terms).
    // @see /test/fixtures/documents.txt
    $task->setDocIds([
      '95615828-70db-v26b-9057-f6cc905dcn6h',
      '77545828-70db-95gb-9057-f6553218dcn6',
    ]);

    // Run Drupal to Drupal replication.
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8081/relaxed/$target_workspace/_all_docs", 0);
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "' . $source_workspace . '", "timeout": 60}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}}', $task);
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8081/relaxed/$target_workspace/_all_docs", 2);

    // Run Drupal to CouchDB replication.
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 0);
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}, "target": {"dbname": "' . $this->targetDb . '", "port": ' . $this->port . '}}', $task);
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 2);

    // Replicate 3 more docs.
    // @see /test/fixtures/documents.txt
    $task->setDocIds([
      '6f9e1f07-e713-4840-bf95-8326c8317800',
      'ad3d5c67-e82a-4faf-a7fd-c5ad3975b622',
      '1da2a674-4740-4edb-ad3d-2e243c9e6821',
    ]);

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "' . $source_workspace . '", "timeout": 60}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}}', $task);
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8081/relaxed/$target_workspace/_all_docs", 5);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}, "target": {"dbname": "' . $this->targetDb . '", "port": ' . $this->port . '}}', $task);
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 5);

    // Shouldn't replicate anything because of non-existing doc ID.
    $task->setDocIds(['non-existing-doc-id']);

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "' . $source_workspace . '", "timeout": 60}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}}', $task);
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8081/relaxed/$target_workspace/_all_docs", 5);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}, "target": {"dbname": "' . $this->targetDb . '", "port": ' . $this->port . '}}', $task);
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 5);

    // Now it should replicate all remaining changes.
    $task = new ReplicationTask();

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "' . $source_workspace . '", "timeout": 60}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}}', $task);
    $this->assertAllDocsNumber("http://replicator:replicator@localhost:8081/relaxed/$target_workspace/_all_docs", 9);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "' . $target_workspace . '", "timeout": 60}, "target": {"dbname": "' . $this->targetDb . '", "port": ' . $this->port . '}}', $task);
    $this->assertAllDocsNumber($this->couchdbUrl . '/' . $this->targetDb . '/_all_docs', 9);
  }

}
