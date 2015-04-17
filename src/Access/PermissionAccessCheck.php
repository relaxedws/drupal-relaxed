<?php

/**
 * @file
 * Contains Drupal\relaxed\Access\PermissionAccessCheck.
 */

namespace Drupal\relaxed\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes.
 */
class PermissionAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account) {
    $defaults = $route->getDefaults();
    if (isset($defaults['_plugin'])
      && substr($defaults['_plugin'], 0, 7) === 'relaxed'
      && $account->hasPermission('perform content replication')) {
      return AccessResult::allowed();
    }
    else {
      $permission = $route->getRequirement('_permission');

      if ($permission === NULL) {
        return AccessResult::neutral();
      }

      // Allow to conjunct the permissions with OR ('+') or AND (',').
      $split = explode(',', $permission);
      if (count($split) > 1) {
        return AccessResult::allowedIfHasPermissions($account, $split, 'AND');
      }
      else {
        $split = explode('+', $permission);
        return AccessResult::allowedIfHasPermissions($account, $split, 'OR');
      }
    }
  }

}
