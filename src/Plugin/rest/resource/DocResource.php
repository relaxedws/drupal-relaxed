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
 *   }
 * )
 *
 * @todo We should probably make it not possible to save '_local' documents
 *   through this resource.
 */
class DocResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function head($workspace, $entities) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }
    // We know there can only be one entity with DELETE requests.
    $entity = reset($entities);

    // @todo Create a event handler and override the ETag that's set by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse(NULL, 200, array('X-Relaxed-ETag' => $entity->_revs_info->rev));
  }

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace, $entities) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }
    foreach ($entities as $entity) {
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      foreach ($entity as $field_name => $field) {
        if (!$field->access('view')) {
          unset($entity->{$field_name});
        }
      }
    }
    // Decide if to return a single entity or multiple revisions.
    $data = \Drupal::request()->query->get('open_revs') ? $entities : reset($entities);
    // @todo Create a event handler and override the ETag that's set by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse($data, 200, array('X-Relaxed-ETag' => $entity->_revs_info->rev));
  }

  /**
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   *
   * @return \Drupal\rest\ResourceResponse
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
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function delete($workspace, $entities) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }
    // We know there can only be one entity with DELETE requests.
    $entity = reset($entities);

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
