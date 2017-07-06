<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\relaxed\Http\ApiResourceResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * @ApiResource(
 *   id = "relaxed:session",
 *   label = "Session",
 *   path = "/_session"
 * )
 */
class SessionApiResource extends ApiResourceBase {

  /**
   * @return ApiResourceResponse
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
