<?php

namespace Drupal\relaxed;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\replication\ReplicationTask\ReplicationTaskInterface;
use Drupal\workspace\ReplicatorInterface;
use Drupal\workspace\WorkspacePointerInterface;
use GuzzleHttp\Psr7\Uri;
use Relaxed\Replicator\ReplicationTask as RelaxedReplicationTask;
use Relaxed\Replicator\Replicator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CouchdbReplicator implements ReplicatorInterface{

  protected $relaxedSettings;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->relaxedSettings = $config_factory->get('relaxed.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    if ($this->setupEndpoint($source) && $this->setupEndpoint($target)) {
      return TRUE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target, $task = NULL) {
    if ($task !== NULL && !$task instanceof ReplicationTaskInterface && !$task instanceof RelaxedReplicationTask) {
      throw new UnexpectedTypeException($task, 'Drupal\replication\ReplicationTask\ReplicationTaskInterface or Relaxed\Replicator\ReplicationTask');
    }
    
    $source_db = $this->setupEndpoint($source);
    $target_db = $this->setupEndpoint($target);

    try {
      if ($task === NULL) {
        $couchdb_task = new RelaxedReplicationTask();
      }
      elseif ($task instanceof ReplicationTaskInterface) {
        $couchdb_task = new RelaxedReplicationTask();
      }
      else {
        $couchdb_task = clone $task;
      }
      
      $couchdb_task->setFilter($task->getFilter());
      $couchdb_task->setParameters($task->getParameters());

      $replicator = new Replicator($source_db, $target_db, $couchdb_task);
      $result = $replicator->startReplication();
      if (isset($result['session_id'])) {
        $workspace_id = $source->getWorkspaceId() ?: $target->getWorkspaceId();
        if (!empty($workspace_id)) {
          $replication_logs = \Drupal::entityTypeManager()
            ->getStorage('replication_log')
            ->useWorkspace($workspace_id)
            ->loadByProperties(['session_id' => $result['session_id']]);
        }
        else {
          $replication_logs = \Drupal::entityTypeManager()
            ->getStorage('replication_log')
            ->loadByProperties(['session_id' => $result['session_id']]);
        }
        return reset($replication_logs);
      }
      else {
        return $this->errorReplicationLog($source, $target);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Relaxed', $e);
      return $this->errorReplicationLog($source, $target);
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
      $port = $uri->getPort();

      if (empty($port)) {
        $port = ($uri->getScheme() == 'https') ? 443 : 80;
      }

      return CouchDBClient::create([
        'url' => (string) $uri,
        'port' => $port,
        'timeout' => 10
      ]);
    }
  }

  protected function errorReplicationLog(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    $time = new \DateTime();
    $history = [
      'start_time' => $time->format('D, d M Y H:i:s e'),
      'end_time' => $time->format('D, d M Y H:i:s e'),
      'session_id' => \md5((\microtime(true) * 1000000)),
      'start_last_seq' => $source->getWorkspace()->getUpdateSeq(),
    ];
    $replication_log_id = $source->generateReplicationId($target);
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = ReplicationLog::loadOrCreate($replication_log_id);
    $replication_log->set('ok', FALSE);
    $replication_log->setSourceLastSeq($source->getWorkspace()->getUpdateSeq());
    $replication_log->setSessionId($history['session_id']);
    $replication_log->setHistory($history);
    $replication_log->save();
    return $replication_log;
  }

}
