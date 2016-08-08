<?php

namespace Drupal\Tests\relaxed\Integration;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Doctrine\CouchDB\CouchDBClient;
use Exception;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;

abstract class ReplicationTestBase extends KernelTestBase {

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
    'workspace',
    'replication',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['multiversion', 'workspace', 'replication', 'relaxed']);
    $this->installEntitySchema('workspace');
    $this->installEntitySchema('workspace_pointer');
    $this->installEntitySchema('user');
    // Create the default workspace because the multiversion_install() hook is
    // not executed in unit tests.
    Workspace::create(['machine_name' => 'live', 'label' => 'Live', 'type' => 'basic'])->save();

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

  /**
   * Replicates content from source and target using the /_replicate endpoint.
   */
  protected function endpointReplicate($data, $endpoint) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_URL => $endpoint,
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
   * Assert that the database contain the correct number of docs.
   *
   * @param $db_url
   * @param $docs_number
   */
  protected function assertAllDocsNumber($db_url, $docs_number) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_URL => $db_url,
    ]);
    $response = curl_exec($curl);
    preg_match('~"total_rows":([/\d+/]*)~', $response, $output);
    $this->assertEquals($docs_number, $output[1], 'The request returned the correct number of docs.');
    curl_close($curl);
  }

}
