<?php

namespace Drupal\relaxed;


use Drupal\relaxed\Entity\RemoteInterface;

interface RemotePointerInterface {

  /**
   * Gets databases from remote.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   * @return array
   */
  public function getRemoteDatabases(RemoteInterface $remote);

  /**
   * Adds pointers for given remote.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   */
  public function addPointers(RemoteInterface $remote);

  /**
   * Adds pointers for all remotes.
   */
  public function addAllPointers();

  /**
   * Cleanup pointers for given remote.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   */
  public function cleanupPointersForRemote(RemoteInterface $remote);

  /**
   * Cleanup pointers that no longer exist for all remotes.
   */
  public function cleanupPointers();

}
