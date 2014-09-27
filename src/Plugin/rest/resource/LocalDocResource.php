<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:local:doc",
 *   label = "Local document",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_local/{docid}",
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
class LocalDocResource extends DocResource {

  /**
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function head($workspace, $entity) {
    if ($entity instanceof ContentEntityInterface && $entity->_local->value == FALSE) {
      throw new NotFoundHttpException();
    }
    return parent::head($workspace, $entity);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace, $entity) {
    if ($entity instanceof ContentEntityInterface && $entity->_local->value == FALSE) {
      throw new NotFoundHttpException();
    }
    return parent::get($workspace, $entity);
  }

  /**
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity = NULL) {
    if ($received_entity instanceof ContentEntityInterface) {
      if (isset($received_entity->_local->value) && $received_entity->_local->value == FALSE) {
        throw new BadRequestHttpException(t('The _local field value can not be set to FALSE when using this endpoint.'));
      }
      else {
        $received_entity->_local->value = TRUE;
      }
    }
    return parent::put($workspace, $existing_entity, $received_entity);
  }

}
