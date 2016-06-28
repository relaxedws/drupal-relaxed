<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\relaxed\Entity\Remote;
use Drupal\relaxed\Entity\RemoteInterface;

/**
 * Provides the Remote check plugin manager.
 */
class RemoteCheckManager extends DefaultPluginManager {

  /**
   * Constructor for RemoteCheckManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/RemoteCheck', $namespaces, $module_handler, 'Drupal\relaxed\Plugin\RemoteCheckInterface', 'Drupal\relaxed\Annotation\RemoteCheck');

    $this->alterInfo('relaxed_remote_check_info');
    $this->setCacheBackend($cache_backend, 'relaxed_remote_check_plugins');
  }

  /**
   * Runs a checks for all Remotes.
   *
   * @return array
   */
  public function runAll() {
    $results = [];
    $remotes = Remote::loadMultiple();
    foreach ($remotes as $remote) {
      $results[$remote->id()] = $this->run($remote);
    }

    return $results;
  }

  /**
   * Runs checks on given Remote.
   *
   * @param \Drupal\relaxed\Entity\RemoteInterface $remote
   * @return array
   */
  public function run(RemoteInterface $remote) {
    $results = [];
    $checks = $this->getDefinitions();
    foreach ($checks as $check) {
      $checker = $this->createInstance($check['id']);
      $checker->execute($remote);
      $results[$check['id']] = [
        'result' => $checker->getResult(),
        'message' => $checker->getMessage(),
      ];
    }

    return $results;
  }
}
