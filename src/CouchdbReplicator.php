<?php

namespace Drupal\relaxed;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\workspace\PointerInterface;
use Drupal\workspace\ReplicatorInterface;
use GuzzleHttp\Psr7\Uri;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replication;


class CouchdbReplicator implements ReplicatorInterface{

  /**
   * @inheritDoc
   */
  public function applies(PointerInterface $source, PointerInterface $target) {
    if ($this->setupEndpoint($source) && $this->setupEndpoint($target)) {
      return TRUE;
    }
  }

  /**
   * @inheritDoc
   */
  public function replicate(PointerInterface $source, PointerInterface $target) {
    $source_db = $this->setupEndpoint($source);
    $target_db = $this->setupEndpoint($target);
    try {
      // Create the replication task.
      $task = new ReplicationTask();
      // Create the replication.
      $replication = new Replication($source_db, $target_db, $task);
      // Generate and set a replication ID.
      $replication->task->setRepId($replication->generateReplicationId());
      // Filter by document IDs if set.
      if (!empty($this->docIds)) {
        $replication->task->setDocIds($this->docIds);
      }
      // Start the replication.
      $replicationResult = $replication->start();
    }
    catch (\Exception $e) {
      \Drupal::logger('Deploy')->info($e->getMessage() . ': ' . $e->getTraceAsString());
      return ['error' => $e->getMessage()];
    }
    // Return the response.
    return $replicationResult;
  }

  protected function setupEndpoint(PointerInterface $pointer) {
    if (!empty($pointer->data()['workspace'])) {
      /** @var ConfigFactoryInterface $config */
      $config = \Drupal::config('relaxed.settings');
      /** @var string $api_root */
      $api_root = trim($config->get('api_root'), '/');
      /** @var WorkspaceInterface $workspace */
      $workspace = Workspace::load($pointer->data()['workspace']);
      $url = Url::fromUri('base:/' . $api_root . '/' . $workspace->getMachineName(), [])
        ->setAbsolute()
        ->toString();
      $uri = new Uri($url);
      $uri = $uri->withUserInfo($config->get('username'), base64_decode($config->get('password')));
    }

    if (!empty($pointer->data()['remote'])) {
      /** @var RemoteInterface $remote */
      $remote = Remote::load($pointer->data()['remote']);
      /** @var Uri $uri */
      $uri = $remote->uri();
      $uri = $uri->withPath($uri->getPath() . '/' . $pointer->data()['database']);
    }

    if ($uri instanceof Uri) {
      $port = $uri->getPort() ?: 80;
      return CouchDBClient::create([
        'url' => (string) $uri,
        'port' => $port
      ]);
    }
  }
}