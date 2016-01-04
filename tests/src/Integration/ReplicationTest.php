<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Integration\ReplicationTest.
 */

namespace Drupal\Tests\relaxed\Integration;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Doctrine\CouchDB\CouchDBClient;
use Exception;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;

/**
 * @group relaxed
 */
class ReplicationTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * CouchDB source database name.
   *
   * @var string
   */
  protected $source_db;

  /**
   * CouchDB target database name.
   *
   * @var string
   */
  protected $target_db;

  /**
   * CouchDB url.
   *
   * @var string
   */
  protected $couchdb_url;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'system',
    'rest',
    'key_value',
    'multiversion',
    'relaxed',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['multiversion', 'relaxed']);
    $this->installEntitySchema('workspace');
    // Create the default workspace because the multiversion_install() hook is
    // not executed in unit tests.
    Workspace::create(['machine_name' => 'default'])->save();

    $this->source_db = 'source';
    $this->target_db = 'target';
    $this->couchdb_url = 'http://localhost:5984';

    // Create a source database.
    $response_code = $this->createDb($this->source_db);
    $this->assertEquals(201, $response_code);

    // Create a target database.
    $response_code = $this->createDb($this->target_db);
    $this->assertEquals(201, $response_code);

    // Load documents from documents.txt and save them in the 'source' database.
    $handle = fopen(realpath(dirname(__FILE__) . '/../..') . '/fixtures/documents.txt', "r");
    if ($handle) {
      $curl = curl_init();
      while (($line = fgets($handle)) !== FALSE) {
        curl_setopt_array($curl, [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $line,
          CURLOPT_URL => "$this->couchdb_url/$this->source_db",
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
          ],
        ]);

        curl_exec($curl);
      }

      fclose($handle);
      curl_close($curl);
    } else {
      $this->fail("Error when reading documents.txt");
    }
  }

  /**
   * Test the replication using CouchDB and PHP replicators.
   */
  public function testReplication() {

    // Test the replication using the CouchDB replicator.

    // Run CouchDB to Drupal replication.
    $this->couchDbReplicate($this->couchdb_url . "/$this->source_db", 'http://replicator:replicator@localhost:8080/relaxed/default');
    sleep(10);

    // Run Drupal to Drupal replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8080/relaxed/default', 'http://replicator:replicator@localhost:8081/relaxed/default');
    sleep(10);

    // Run Drupal to CouchDB replication.
    $this->couchDbReplicate('http://replicator:replicator@localhost:8081/relaxed/default', $this->couchdb_url . "/$this->target_db");
    sleep(10);

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

    // Delete target database.
    $response_code = $this->deleteDb($this->target_db);
    $this->assertEquals(200, $response_code);

    // Test the replication using the PHP replicator.

    // Create a target database.
    $response_code = $this->createDb($this->target_db);
    $this->assertEquals(201, $response_code);

    // Run CouchDB to Drupal replication.
    $this->phpReplicate('{"source": {"dbname": "' . $this->source_db . '"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}');
    sleep(10);

    // Run Drupal to Drupal replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}');
    sleep(10);

    // Run Drupal to CouchDB replication.
    $this->phpReplicate('{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"dbname": "' . $this->target_db . '"}}');
    sleep(10);

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

  /**
   * Creates a new database.
   */
  protected function createDb($db_name) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_URL => "$this->couchdb_url/$db_name",
    ]);

    curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $code;
  }

  /**
   * Creates delete a database.
   */
  protected function deleteDb($db_name) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_CUSTOMREQUEST => 'DELETE',
      CURLOPT_URL => "$this->couchdb_url/$db_name",
    ]);

    curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $code;
  }

  /**
   * Replicates content from source and target using the CouchDB replicator.
   */
  protected function couchDbReplicate($source, $target) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => '{"source": "' . $source . '", "target": "' . $target . '", "worker_processes": 1}',
      CURLOPT_URL => "$this->couchdb_url/_replicate",
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
  }

  /**
   * Replicates content from source to target using the PHP replicator.
   */
  protected function phpReplicate($data) {
    $json = json_decode($data, true);
    if (json_last_error() != JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON.');
    }

    $source = CouchDBClient::create($json['source']);
    $target = CouchDBClient::create($json['target']);

    $task = new ReplicationTask();
    $replicator = new Replicator($source, $target, $task);

    return $replicator->startReplication();
  }

}
