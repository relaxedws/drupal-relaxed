<?php

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * Defines the Remote entity.
 *
 * @ConfigEntityType(
 *   id = "remote",
 *   label = @Translation("Remote"),
 *   handlers = {
 *     "list_builder" = "Drupal\relaxed\Entity\RemoteListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "form" = {
 *       "default" = "Drupal\relaxed\Form\RemoteForm",
 *       "add" = "Drupal\relaxed\Form\RemoteForm",
 *       "edit" = "Drupal\relaxed\Form\RemoteForm",
 *       "delete" = "Drupal\relaxed\Form\RemoteDeleteForm"
 *     }
 *   },
 *   config_prefix = "remote",
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
 *     "uri",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/relaxed/{remote}",
 *     "edit-form" = "/admin/config/services/relaxed/{remote}/edit",
 *     "delete-form" = "/admin/config/services/relaxed/{remote}/delete",
 *     "collection" = "/admin/config/services/relaxed"
 *   }
 * )
 */
class Remote extends ConfigEntityBase implements RemoteInterface {

  /**
   * The Remote ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Remote label.
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
   *   Base64 encoded uri string
   */
  protected $uri;

  public function uri() {
    return new Uri(base64_decode($this->uri));
  }

  public function withoutUserInfo() {
    return $this->uri()->withUserInfo(null);
  }

  public function username() {
    $user_info = explode(':', $this->uri()->getUserInfo());
    return $user_info[0];
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    \Drupal::service('relaxed.remote_pointer')->addPointers($this);
    parent::postSave($storage, $update);
  }
}
