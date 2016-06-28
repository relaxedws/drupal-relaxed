<?php

namespace Drupal\relaxed;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
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
   * {@inheritDoc}
   */
  public function getRemoteDatabases(RemoteInterface $remote) {
    $uri = $remote->uri();
    try {
      $response = $this->httpClient->request('GET', $uri . '/_all_dbs');
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
   * {@inheritDoc}
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
}