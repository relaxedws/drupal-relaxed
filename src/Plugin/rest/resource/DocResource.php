<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:doc",
 *   label = "Document",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}",
 *   },
 *   uri_parameters = {
 *     "canonical" = {
 *       "doc" = {
 *         "type" = "entity_uuid:workspace",
 *       },
 *       "docid" = {
 *         "type" = "entity_uuid",
 *         "rev" = TRUE,
 *       }
 *     }
 *   }
 * )
 */
class DocResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function head($workspace, $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      throw new NotFoundHttpException();
    }
    // @todo Create a event handler and override the ETag that's set by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse(NULL, 200, array('X-Relaxed-ETag' => $entity->_revs_info->rev));
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function get($workspace, $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      throw new NotFoundHttpException();
    }
    if (!$entity->access('view')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($entity as $field_name => $field) {
      if (!$field->access('view')) {
        unset($entity->{$field_name});
      }
    }
    // @todo Create a event handler and override the ETag that's set by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse($entity, 200, array('X-Relaxed-ETag' => $entity->_revs_info->rev));
  }

  /**
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity = NULL) {
    if (!$received_entity instanceof ContentEntityInterface) {
      throw new BadRequestHttpException(t('No content received'));
    }

    // Check entity and field level access.
    if (!$received_entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($received_entity as $field_name => $field) {
      if (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
      }
    }

    // Validate the received data before saving.
    $this->validate($received_entity);
    try {
      $received_entity->save();
      $rev = $received_entity->_revs_info->rev;
      return new ResourceResponse(array('ok' => TRUE, 'id' => $received_entity->uuid(), 'rev' => $rev), 201, array('ETag' => $rev));
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, NULL, $e);
    }
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function delete($workspace, ContentEntityInterface $entity) {
    try {
      // @todo: Access check.
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new HttpException(500, NULL, $e);
    }
    return new ResourceResponse(array('ok' => TRUE), 200);
  }
}
