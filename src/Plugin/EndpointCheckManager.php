<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\EndpointCheckManager.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\relaxed\Entity\Endpoint;
use Drupal\relaxed\Entity\EndpointInterface;

/**
 * Provides the Endpoint check plugin manager.
 */
class EndpointCheckManager extends DefaultPluginManager {

  /**
   * Constructor for EndpointCheckManager objects.
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
    parent::__construct('Plugin/EndpointCheck', $namespaces, $module_handler, 'Drupal\relaxed\Plugin\EndpointCheckInterface', 'Drupal\relaxed\Annotation\EndpointCheck');

    $this->alterInfo('relaxed_endpoint_check_info');
    $this->setCacheBackend($cache_backend, 'relaxed_endpoint_check_plugins');
  }

  /**
   * Runs a checks for all Endpoints.
   *
   * @return array
   */
  public function runAll() {
    $results = [];
    $endpoints = Endpoint::loadMultiple();
    foreach ($endpoints as $endpoint) {
      $results[$endpoint->id()] = $this->run($endpoint);
    }

    return $results;
  }

  /**
   * Runs checks on given Endpoint.
   *
   * @param \Drupal\relaxed\Entity\EndpointInterface $endpoint
   * @return array
   */
  public function run(EndpointInterface $endpoint) {
    $results = [];
    $checks = $this->getDefinitions();
    foreach ($checks as $check) {
      $checker = $this->createInstance($check['id']);
      $checker->execute($endpoint);
      $results[$check['id']] = [
        'result' => $checker->getResult(),
        'message' => $checker->getMessage(),
      ];
    }

    return $results;
  }
}
