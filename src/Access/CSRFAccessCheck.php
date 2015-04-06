<?php

/**
 * @file
 * Contains Drupal\relaxed\Access\CSRFAccessCheck.
 */

namespace Drupal\relaxed\Access;

use Drupal\rest\Access\CSRFAccessCheck as CoreCSRFAccessCheck;
use Symfony\Component\Routing\Route;

/**
 * Access protection against CSRF attacks.
 */
class CSRFAccessCheck extends CoreCSRFAccessCheck {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    $defaults = $route->getDefaults();
    if (isset($defaults['_plugin']) && substr($defaults['_plugin'], 0, 7) === 'relaxed') {
      // Disable CSRF access check for routes defined by Relaxed module.
      // @todo Find more info/details about this, it could be a security risk.
      return FALSE;
    }
    else {
      return parent::applies($route);
    }
  }

}
