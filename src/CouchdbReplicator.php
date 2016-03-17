<?php

namespace Drupal\relaxed;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\workspace\ReplicatorInterface;
use Drupal\workspace\WorkspacePointerInterface;
use GuzzleHttp\Psr7\Uri;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;


class CouchdbReplicator implements ReplicatorInterface{

  protected $relaxedSettings;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->relaxedSettings = $config_factory->get('relaxed.settings');
  }

  /**
   * @inheritDoc
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    if ($this->setupEndpoint($source) && $this->setupEndpoint($target)) {
      return TRUE;
    }
  }

  /**
   * @inheritDoc
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
     $source_db = $this->setupEndpoint($source);
     $target_db = $this->setupEndpoint($target);

    try {
      $task = new ReplicationTask();
      $replicator = new Replicator($source_db, $target_db, $task);
      return $replicator->startReplication();
    }
    catch (\Exception $e) {
      watchdog_exception('Relaxed', $e);
      return ['error' => $e->getMessage()];
    }
  }

  protected function setupEndpoint(WorkspacePointerInterface $pointer) {
    if (!empty($pointer->getWorkspaceId())) {
      /** @var string $api_root */
      $api_root = trim($this->relaxedSettings->get('api_root'), '/');
      /** @var WorkspaceInterface $workspace */
      $workspace = $pointer->getWorkspace();
      $url = Url::fromUri('base:/' . $api_root . '/' . $workspace->getMachineName(), [])
        ->setAbsolute()
        ->toString();
      $uri = new Uri($url);
      $uri = $uri->withUserInfo(
        $this->relaxedSettings->get('username'),
        base64_decode($this->relaxedSettings->get('password'))
      );
    }

    if (!empty($pointer->get('remote_pointer')->target_id) && !empty($pointer->get('remote_database')->value)) {
      /** @var RemoteInterface $remote */
      $remote = $pointer->get('remote_pointer')->entity;
      /** @var Uri $uri */
      $uri = $remote->uri();
      $uri = $uri->withPath($uri->getPath() . '/' . $pointer->get('remote_database')->value);
    }

    if ($uri instanceof Uri) {
      $port = $uri->getPort() ?: 80;
      return CouchDBClient::create([
        'url' => (string) $uri,
        'port' => $port,
        'timeout' => 10
      ]);
    }
  }
}