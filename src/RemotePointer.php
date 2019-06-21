<?php

namespace Drupal\relaxed;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\Entity\WorkspacePointer;
use Drupal\workspace\WorkspacePointerInterface;
use GuzzleHttp\ClientInterface;

class RemotePointer implements RemotePointerInterface {

  /** @var \GuzzleHttp\ClientInterface  */
  protected $httpClient;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface  */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ClientInterface $http_client) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function loadOrCreate(RemoteInterface $remote, $database) {
    /** @var \Drupal\workspace\WorkspacePointerInterface $pointer */
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['remote_pointer' => $remote->id(), 'remote_database' => $database]);
    $pointer = reset($pointers);
    if (!($pointer instanceof WorkspacePointerInterface)) {
      $pointer = WorkspacePointer::create();
      $pointer->set('remote_pointer', $remote->id());
      $pointer->set('remote_database', $database);
    }
    return $pointer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteDatabases(RemoteInterface $remote) {
    $uri = $remote->uri();
    $options = [];
    // If the self signed certificates are allowed then verify value should
    // be FALSE.
    if (Settings::get('allow_self_signed_certificates', FALSE)) {
      $options = ['verify' => FALSE];
    }
    try {
      $response = $this->httpClient->request('GET', $uri . '/_all_dbs', $options);
      if ($response->getStatusCode() === 200) {
        return json_decode($response->getBody());
      }
    }
    catch (\Exception $e) {
      watchdog_exception('relaxed', $e);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addPointers(RemoteInterface $remote) {
    $databases = $this->getRemoteDatabases($remote);
    foreach ($databases as $database) {
      $pointer = $this->loadOrCreate($remote, $database);
      $pointer->setName($remote->label() . ': ' . $database);
      $pointer->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addAllPointers() {
    $remotes = Remote::loadMultiple();
    foreach ($remotes as $remote) {
      $this->addPointers($remote);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupPointersForRemote(RemoteInterface $remote) {
    $databases = $this->getRemoteDatabases($remote);

    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['remote_pointer' => $remote->id()]);

    if (empty($databases)) {
      /** @var WorkspacePointerInterface $pointer */
      foreach ($pointers as $pointer) {
        // Set all remote workspaces as not available, maybe there is a
        // connection problem and the remote is not available for the moment.
        $pointer->setWorkspaceAvailable(FALSE)->save();
      }
    }
    else {
      /** @var WorkspacePointerInterface $pointer */
      foreach ($pointers as $pointer) {
        // Loop over all the pointers for our given remote and compare the
        // remote_database name with the ones we received from our remote.
        $database_name = $pointer->get('remote_database')->value;
        if (!empty($database_name) && !in_array($database_name, $databases)) {
          $deployments = $this->entityTypeManager
            ->getStorage('replication')
            ->loadByProperties(['source' => $pointer->id()]);
          $deployments += $this->entityTypeManager
            ->getStorage('replication')
            ->loadByProperties(['target' => $pointer->id()]);
          /** @var Replication $deployment */
          foreach ($deployments as $deployment) {
            $replication_status = $deployment->get('replication_status')->value;
            if (!in_array($replication_status, [Replication::QUEUED, Replication::REPLICATING])) {
              continue;
            }
            $deployment->set('fail_info', t('The workspace pointer does not exist, this could be caused by the missing source or target workspace.'));
            $deployment->setReplicationStatusFailed()->save();
          }
          $pointer->delete();
        }
        elseif (!$pointer->getWorkspaceAvailable()) {
          $pointer->setWorkspaceAvailable()->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupPointers() {
    $remotes = Remote::loadMultiple();
    foreach ($remotes as $remote) {
      $this->cleanupPointersForRemote($remote);
    }
  }

}
