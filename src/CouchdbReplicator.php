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
use Relaxed\Replicator\Replicator;


class CouchdbReplicator implements ReplicatorInterface{

  protected $relaxedSettings;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->relaxedSettings = $config_factory->get('relaxed.settings');
  }

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
      $task = new ReplicationTask();
      $replicator = new Replicator($source_db, $target_db, $task);
      return $replicator->startReplication();
    }
    catch (\Exception $e) {
      watchdog_exception('Relaxed', $e);
      return ['error' => $e->getMessage()];
    }
  }

  protected function setupEndpoint(PointerInterface $pointer) {
    if (!empty($pointer->data()['workspace'])) {
      /** @var string $api_root */
      $api_root = trim($this->relaxedSettings->get('api_root'), '/');
      /** @var WorkspaceInterface $workspace */
      $workspace = Workspace::load($pointer->data()['workspace']);
      $url = Url::fromUri('base:/' . $api_root . '/' . $workspace->getMachineName(), [])
        ->setAbsolute()
        ->toString();
      $uri = new Uri($url);
      $uri = $uri->withUserInfo(
        $this->relaxedSettings->get('username'),
        base64_decode($this->relaxedSettings->get('password'))
      );
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
        'port' => $port,
        'timeout' => 10
      ]);
    }
  }
}