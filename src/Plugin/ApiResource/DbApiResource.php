<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\Http\ApiResourceResponse;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * @ApiResource(
 *   id = "db",
 *   label = "Workspace",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   path = "/{db}",
 *   no_cache = TRUE
 * )
 */
class DbApiResource extends ApiResourceBase {

  /**
   * @param $entity
   *
   * @return ApiResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function head($entity) {
    if (!$entity instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }
    $response = new ApiResourceResponse(NULL, 200);
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * @param $entity
   *
   * @return ApiResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($entity) {
    $this->checkWorkspaceExists($entity);
    // @todo: {@link https://www.drupal.org/node/2600382 Access check.}
    $response =  new ApiResourceResponse($entity, 200);
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * @param $entity
   *
   * @return ApiResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException
   */
  public function put($entity) {
    $this->checkWorkspaceExists($entity);
    if (!$entity->isNew()) {
      throw new PreconditionFailedHttpException(t('The workspace could not be created, it already exists.'));
    }
    elseif ($entity->validate()->count() != 0) {
      throw new NotFoundHttpException(t('Invalid workspace.'));
    }

    try {
      $entity->save();
    }
    catch (\Exception $e) {
      throw new HttpException(500, t($e->getMessage()), $e);
    }

    $response = new ApiResourceResponse(['ok' => TRUE], 201);
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * @param $workspace
   * @param ContentEntityInterface $entity
   *
   * @return ApiResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function post($workspace, ContentEntityInterface $entity = NULL) {
    // If the workspace parameter is a string it means it could not be upcasted
    // to an entity because none existed.
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException(t('Database does not exist'));
    }
    elseif (empty($entity)) {
      throw new BadRequestHttpException(t('No content received'));
    }

    // Check entity and field level access.
    if (!$entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($entity as $field_name => $field) {
      if ($entity instanceof UserInterface) {
        // For user fields we need to check 'edit' permissions.
        if (!$field->access('edit')) {
          throw new AccessDeniedHttpException(t('Access denied on creating field @field.', ['@field' => $field_name]));
        }
      }
      elseif (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', ['@field' => $field_name]));
      }
    }

    // Validate the received data before saving.
    $this->validate($entity);
    try {
      $entity->save();
      $rev = $entity->_rev->value;

      $response = new ApiResourceResponse(['ok' => TRUE, 'id' => $entity->uuid(), 'rev' => $rev], 201, ['ETag' => $rev]);
      $response->addCacheableDependency($entity);

      return $response;
    }
    catch (\Exception $e) {
      throw new HttpException(500, $e->getMessage(), $e);
    }
  }

  /**
   * @param WorkspaceInterface $entity
   *
   * @return ApiResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete(WorkspaceInterface $entity) {
    if (!$entity->isPublished()) {
      throw new HttpException(500, t('Workspace does not exist.'));
    }
    try {
      // @todo: {@link https://www.drupal.org/node/2600382 Access check.}
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new HttpException(500, $e->getMessage(), $e);
    }

    $response = new ApiResourceResponse(['ok' => TRUE], 200);
    $response->addCacheableDependency($entity);

    return $response;
  }

}
