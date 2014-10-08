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

  public function head($workspace, $entities, $field_name, $delta, $file_uuid, $scheme, $filename) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }

    $file = \Drupal::entityManager()->loadEntityByUuid('file', $file_uuid);
    if (!$file) {
      throw new NotFoundHttpException();
    }

    $file_contents = file_get_contents($file->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    return new ResourceResponse(
      NULL,
      200,
      array(
        'X-Relaxed-ETag' => $encoded_digest,
        'Content-Length' => $file->getSize(),
        'Content-MD5' => $encoded_digest,
      )
    );
  }

  public function get($workspace, $entities, $field_name, $delta, $file_uuid, $scheme, $filename) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }

    $file = \Drupal::entityManager()->loadEntityByUuid('file', $file_uuid);
    if (!$file) {
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

    $file_contents = file_get_contents($file->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $resource = new ResourceResponse(
      $file_contents,
      200,
      array(
        'Content-Type' => $file->getMimeType(),
        'X-Relaxed-ETag' => $encoded_digest,
        'Content-Length' => $file->getSize(),
        'Content-MD5' => $encoded_digest,
      )
    );

    return $resource;
  }
}