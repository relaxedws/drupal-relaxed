<?php

namespace Drupal\relaxed;


use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;
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
      $this->message = $e->getMessage();
      watchdog_exception('relaxed', $e);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function addPointers(RemoteInterface $remote) {
    $databases = $this->getRemoteDatabases($remote);
    foreach ($databases as $database) {
      $pointer = new Pointer(
        'remote:' . $remote->id() . ':' . $database,
        $remote->label() . ': ' . $database,
        [
          'remote' => $remote->id(),
          'database' => $database
        ]
      );
      \Drupal::service('workspace.pointer')->add($pointer);
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