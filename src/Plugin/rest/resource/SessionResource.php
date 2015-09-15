<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\SessionResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @RestResource(
 *   id = "relaxed:session",
 *   label = "Session",
 *   uri_paths = {
 *     "canonical" = "/_session",
 *   }
 * )
 */
class SessionResource extends ResourceBase {

  /**
   * @return ResourceResponse
   */
  public function get() {
    $account = \Drupal::currentUser();
    if ($account->isAnonymous()) {
      throw new UnauthorizedHttpException('', 'Username or password was not recognized.');
    }

    $roles = array_values($account->getRoles());
    $admin_role = \Drupal::config('user.settings')->get('admin_role');
    if (in_array($admin_role, $roles)) {
      $roles[] = '_admin';
    }

    return new ResourceResponse(
      array(
        'info' => array(),
        'ok' => TRUE,
        'userCtx' => array(
          'user' => $account->getUsername(),
          'roles' => $roles,
        ),
      ),
      200
    );
  }

}
