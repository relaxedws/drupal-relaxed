<?php

namespace Drupal\relaxed;


use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\workspace\Entity\WorkspacePointer;
use Drupal\workspace\Pointer;

class RemotePointer implements RemotePointerInterface {

  /**
   * {@inheritDoc}
   */
  public function getRemoteDatabases(RemoteInterface $remote) {
    $uri = $remote->uri();
    $client = \Drupal::httpClient();
    try {
      $response = $client->request('GET', $uri . '/_all_dbs');
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
      WorkspacePointer::create([
        'name' => $remote->label() . ': ' . $database,
        'remote_pointer' => $remote->id(),
        'remote_database' => $database,
      ])->save();
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