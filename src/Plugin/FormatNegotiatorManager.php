<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\relaxed\Annotation\FormatNegotiator;

/**
 * Relaxed format negotiator manager.
 */
class FormatNegotiatorManager extends DefaultPluginManager implements FormatNegotiatorManagerInterface {

  /**
   * Constructor for FormatNegotiatorManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to                                                                 use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FormatNegotiator', $namespaces, $module_handler, FormatNegotiatorInterface::class, FormatNegotiator::class);

    $this->alterInfo('relaxed_format_negotiator_info');
    $this->setCacheBackend($cache_backend, 'relaxed:format_negotiator:plugins');
  }

  /**
   * Returns an array of all available formats from all plugins.
   *
   * @return array
   */
  public function availableFormats() {
    $available = [];
    $formats = array_map(function ($definition) {
      return $definition['formats'];
    }, $this->getDefinitions());

    foreach ($formats as $formats) {
      $available = array_merge($available, $formats);
    }

    return array_unique($available);
  }

  /**
   * {@inheritdoc}
   */
  public function select($format, $method) {
    foreach ($this->getDefinitions() as $definition) {
      $plugin = $this->createInstance($definition['id'], $definition);

      if ($plugin->applies($format, $method)) {
        return $plugin;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();

    // Sort the definitions by priority.
    uasort($definitions, function ($a, $b) {
      $a_weight = $a['priority'];
      $b_weight = $b['priority'];

      if ($a_weight == $b_weight) {
        return 0;
      }

      return ($a_weight < $b_weight) ? 1 : -1;
    });

    return $definitions;
  }

}
