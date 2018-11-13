<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * @RestResource(
 *   id = "relaxed:db",
 *   label = "Workspace",
 *   serialization_class = {
 *     "canonical" = "Drupal\multiversion\Entity\WorkspaceInterface",
 *     "post" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}",
 *   },
 *   no_cache = TRUE
 * )
 */
class DbResource extends ResourceBase {

  /**
   * @param $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function head($entity) {
    $this->checkWorkspaceExists($entity);
    $response = new ResourceResponse(NULL, 200);
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * @param $entity
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($entity) {
    $this->checkWorkspaceExists($entity);
    // @todo: {@link https://www.drupal.org/node/2600382 Access check.}
    $response =  new ResourceResponse($entity, 200);
    $response->addCacheableDependency($entity);

    return $response;
  }

  /**
   * @param $entity
   *
   * @return \Drupal\rest\ModifiedResourceResponse
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

    return new ModifiedResourceResponse(['ok' => TRUE], 201);
  }

  /**
   * @param $workspace
   * @param ContentEntityInterface $entity
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function post($workspace, ContentEntityInterface $entity = NULL) {
    // If the workspace parameter is a string it means it could not be upcasted
    // to an entity because none existed.
    $this->checkWorkspaceExists($workspace);
    if (empty($entity)) {
      throw new BadRequestHttpException(t('No content received.'));
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

      return new ModifiedResourceResponse(['ok' => TRUE, 'id' => $entity->uuid(), 'rev' => $rev], 201, ['ETag' => $rev]);
    }
    catch (\Exception $e) {
      throw new HttpException(500, $e->getMessage(), $e);
    }
  }

  /**
   * @param WorkspaceInterface $entity
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function delete(WorkspaceInterface $entity) {
    if (!$entity->isPublished()) {
      throw new HttpException(500, t('Workspace does not exist.'));
    }
    try {
      // @todo: {@link https://www.drupal.org/node/2600382 Access check.}
      $entity->delete();
      // Run a cron job.
      \Drupal::service('cron')->run();
    }
    catch (\Exception $e) {
      throw new HttpException(500, $e->getMessage(), $e);
    }

    return new ModifiedResourceResponse(['ok' => TRUE], 200);
  }

}
