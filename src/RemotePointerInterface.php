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
   * Update pointers for given remote depending on workspace availability.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   */
  public function updatePointersForRemote(RemoteInterface $remote);

  /**
   * Update pointers for all remotes.
   */
  public function updatePointers();

}
