<?php

/**
 * @file
 * Contains \Drupal\relaxed\Entity\Endpoint.
 */

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\relaxed\EndpointPluginCollection;

/**
 * Defines the Endpoint entity.
 *
 * @ConfigEntityType(
 *   id = "endpoint",
 *   label = @Translation("Endpoint"),
 *   handlers = {
 *     "list_builder" = "Drupal\relaxed\Entity\EndpointListBuilder",
 *     "form" = {
 *       "add" = "Drupal\relaxed\Form\EndpointAddForm",
 *       "edit" = "Drupal\relaxed\Form\EndpointForm",
 *       "delete" = "Drupal\relaxed\Form\EndpointDeleteForm"
 *     }
 *   },
 *   config_prefix = "endpoint",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/relaxed/{endpoint}",
 *     "edit-form" = "/admin/config/services/relaxed/{endpoint}/edit",
 *     "delete-form" = "/admin/config/services/relaxed/{endpoint}/delete",
 *     "collection" = "/admin/config/services/relaxed"
 *   }
 * )
 */
class Endpoint extends ConfigEntityBase implements EndpointInterface, EntityWithPluginCollectionInterface {

  /**
   * The Endpoint ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Endpoint label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $uuid;

  /**
   * @var string
   */
  protected $plugin;

  /**
   * @var array
   */
  protected $configuration = [];

  /**
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * Encapsulates the creation of the endpoint's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The endpoint's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new EndpointPluginCollection(\Drupal::service('plugin.manager.endpoint'), $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->getPluginCollection()->addInstanceId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }
}
