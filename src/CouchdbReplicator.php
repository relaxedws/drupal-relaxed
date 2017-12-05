<?php

namespace Drupal\relaxed;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\SensitiveDataTransformer;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\ReplicationTask\ReplicationTaskInterface;
use Drupal\workspace\ReplicatorInterface;
use Drupal\workspace\WorkspacePointerInterface;
use GuzzleHttp\Psr7\Uri;
use Relaxed\Replicator\ReplicationTask as RelaxedReplicationTask;
use Relaxed\Replicator\Replicator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CouchdbReplicator implements ReplicatorInterface{

  /**
   * Relaxed configuration settings.
   */
  protected $relaxedSettings;

  /**
   * Relaxed sensitive data transformer service.
   *
   * @var Drupal\relaxed\SensitiveDataTransformer
   */
  protected $transformer;

  public function __construct(ConfigFactoryInterface $config_factory, SensitiveDataTransformer $transformer) {
    $this->relaxedSettings = $config_factory->get('relaxed.settings');
    $this->transformer = $transformer;
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
      if ($task === NULL || $task instanceof ReplicationTaskInterface) {
        $couchdb_task = new RelaxedReplicationTask();
      }
      else {
        $couchdb_task = clone $task;
      }

      if ($task !== NULL) {
        $couchdb_task->setFilter($task->getFilter());
        $couchdb_task->setParameters($task->getParameters());
        $changes_limit = \Drupal::config('replication.settings')->get('changes_limit');
        $couchdb_task->setLimit($changes_limit ?: $task->getLimit());
        $bulk_docs_limit = \Drupal::config('replication.settings')->get('changes_limit');
        $couchdb_task->setBulkDocsLimit($bulk_docs_limit ?: $task->getBulkDocsLimit());

        $replication_log_id = $source->generateReplicationId($target, $task);
        /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
        $replication_logs = \Drupal::entityTypeManager()
          ->getStorage('replication_log')
          ->loadByProperties(['uuid' => $replication_log_id]);
        $replication_log = reset($replication_logs);
        $since = 0;
        if (!empty($replication_log) && $replication_log->get('ok')->value == TRUE && $replication_log_history = $replication_log->getHistory()) {
          $dw = $replication_log_history[0]['docs_written'];
          $mf = $replication_log_history[0]['missing_found'];
          if ($dw !== NULL && $mf !== NULL && $dw == $mf) {
            $since = $replication_log->getSourceLastSeq() ?: $since;
          }
        }
        $couchdb_task->setSinceSeq($since);
      }

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
        return $this->errorReplicationLog($source, $target, $task);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Relaxed', $e);
      return $this->errorReplicationLog($source, $target, $task);
    }
  }

  public function setupEndpoint(WorkspacePointerInterface $pointer) {
    if (!empty($pointer->getWorkspaceId())) {
      /** @var string $api_root */
      $api_root = trim($this->relaxedSettings->get('api_root'), '/');
      /** @var WorkspaceInterface $workspace */
      $workspace = $pointer->getWorkspace();
      $url = Url::fromUri('base:/' . $api_root . '/' . $workspace->getMachineName(), []);
      // This is a workaround for the case when the site/server is not configured
      // correctly and 'base:/' returns the URL with 'http' instead of 'https';
      if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
        $url->setOption('https', TRUE);
      }
      $url = $url->setAbsolute()->toString();
      $uri = new Uri($url);
      $uri = $uri->withUserInfo(
        $this->relaxedSettings->get('username'),
        $this->transformer->get($this->relaxedSettings->get('password'))
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

  protected function errorReplicationLog(WorkspacePointerInterface $source, WorkspacePointerInterface $target, ReplicationTaskInterface $task = NULL) {
    $time = new \DateTime();
    $last_sequence = ($source->getWorkspace() instanceof WorkspaceInterface) ? $source->getWorkspace()->getUpdateSeq() : 0;
    $history = [
      'start_time' => $time->format('D, d M Y H:i:s e'),
      'end_time' => $time->format('D, d M Y H:i:s e'),
      'session_id' => \md5((\microtime(true) * 1000000)),
      'start_last_seq' => $last_sequence,
    ];
    $replication_log_id = $source->generateReplicationId($target, $task);
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = ReplicationLog::loadOrCreate($replication_log_id);
    $replication_log->set('ok', FALSE);
    $replication_log->setSourceLastSeq($last_sequence);
    $replication_log->setSessionId($history['session_id']);
    $replication_log->setHistory($history);
    $replication_log->save();
    return $replication_log;
  }

}
