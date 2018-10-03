<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\relaxed\Http\ApiResourceResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * @ApiResource(
 *   id = "session",
 *   label = "Session",
 *   path = "/_session"
 * )
 */
class SessionApiResource extends ApiResourceBase {

  /**
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get() {
    $account = \Drupal::currentUser();
    if ($account->isAnonymous()) {
      throw new UnauthorizedHttpException('', 'Username or password was not recognized.');
    }

    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    $roles = $account->getRoles();

    // Query the users roles to see if any have admin.
    $admin_role_count = $role_storage->getQuery()
      ->condition('id', $roles)
      ->condition('is_admin', TRUE)
      ->count()
      ->execute();

    // Add computed '_admin' role to list if user has any admin flagged role.
    if ($admin_role_count > 0) {
      $roles[] = '_admin';
    }

    $response = new ApiResourceResponse(
      [
        'info' => [],
        'ok' => TRUE,
        'userCtx' => [
          'user' => $account->getAccountName(),
          'roles' => $roles,
        ],
      ],
      200
    );

    $cacheable_metadata = new CacheableMetadata();
    $response->addCacheableDependency($cacheable_metadata->setCacheContexts(['user']));
    return $response;
  }

}
