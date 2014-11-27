<?php

/**
 * @file
 * Contains \Drupal\relaxed_test\RelaxedTestAccessControlHandler.
 */

namespace Drupal\relaxed_test;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the entity_test_relaxed entity type.
 */
class RelaxedTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('view entity_test_relaxed entity')) {
      $result = AccessResult::allowed()->cachePerRole();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::access($entity, $operation, $langcode, $account, TRUE)->cachePerRole();
    return $return_as_object ? $result : $result->isAllowed();
  }
}
