<?php

/**
 * @file
 * Contains \Drupal\relaxed\Entity\ReplicationLogAccessControlHandler.
 */

namespace Drupal\relaxed\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the replication_log entity type.
 */
class ReplicationLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'restful put relaxed:local:doc');
  }
}
