<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Contract for API resource plugins.
 */
interface ApiResourceInterface extends PluginInspectionInterface, CacheableDependencyInterface {

}
