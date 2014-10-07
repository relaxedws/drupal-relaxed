<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AttachmentResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:attachment",
 *   label = "Attachment",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Field\FieldItemInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}/{field_name}/{delta}/{uuid}/{scheme}/{filename}",
 *   }
 * )
 */
class AttachmentResource extends ResourceBase {

  public function head($workspace, $entities, $attachment) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }

    return new ResourceResponse(NULL, 200, array('X-Relaxed-ETag' => ''));
  }

  public function get($workspace, $entities, $attachment) {
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

    return new ResourceResponse($attachment, 200, NULL);
  }
}