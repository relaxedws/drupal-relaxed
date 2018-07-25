<?php

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Manages ReplicationFilter plugin implementations.
 *
 * @see \Drupal\relaxed\Annotation\ReplicationFilter
 * @see \Drupal\relaxed\Plugin\ReplicationFilterInterface
 * @see \Drupal\relaxed\Plugin\ReplicationFilter\ReplicationFilterBase
 * @see plugin_api
 */
interface ReplicationFilterManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

}
