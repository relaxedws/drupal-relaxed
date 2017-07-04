<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\relaxed\Annotation\ApiResource;

/**
 * API resource plugin manager.
 */
class ApiResourceManager extends DefaultPluginManager implements ApiResourceManagerInterface {

  /**
   * Constructor for ApiResourceManager objects.
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
    parent::__construct('Plugin/ApiResource', $namespaces, $module_handler, ApiResourceInterface::class, ApiResource::class);

    $this->alterInfo('relaxed_api_resource_info');
    $this->setCacheBackend($cache_backend, 'relaxed:api_resource:plugins');
  }

}
