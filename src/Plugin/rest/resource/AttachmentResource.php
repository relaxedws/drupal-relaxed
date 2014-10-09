<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AttachmentResource.
 */

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

    if (in_array(file_uri_scheme($file->getFileUri()), array('public', 'private')) == FALSE) {
      $response = new ResourceResponse('', 200, array('Content-Type' => $file->getMimeType()));
    }
    else {
      $file_contents = file_get_contents($file->getFileUri());
      $encoded_digest = base64_encode(md5($file_contents));
      $response = new ResourceResponse(
        $file_contents,
        200,
        array(
          'Content-Type' => array($file->getMimeType()),
          'X-Relaxed-ETag' => $encoded_digest,
          'Content-Length' => $file->getSize(),
          'Content-MD5' => $encoded_digest,
        )
      );
    }

    return $response;
  }

  public function put($workspace, $existing_entity, $field_name, $delta, $file_uuid, $scheme, $filename, ContentEntityInterface $received_entity = NULL) {
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
      return new ResourceResponse(
        array(
          'ok' => TRUE,
          'id' => $received_entity->uuid(),
          'rev' => $rev),
        201,
        array(
          'ETag' => $rev,
          'Location' => $received_entity->getFileUri(),
        )
      );
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, NULL, $e);
    }
  }

  public function delete($workspace, $entities, $field_name, $delta, $file_uuid, $scheme, $filename) {
    if (empty($entities) || is_string($entities)) {
      throw new NotFoundHttpException();
    }

    $file = \Drupal::entityManager()->loadEntityByUuid('file', $file_uuid);
    if (!$file) {
      throw new NotFoundHttpException();
    }

    // We know there can only be one entity with DELETE requests.
    $entity = reset($entities);

    try {
      // @todo: Access check.
      $entity->{$field_name}[$delta]->entity->delete();
      $entity->save();
      $rev = $entity->_revs_info->rev;
      return new ResourceResponse(
        array(
          'ok' => TRUE,
          'id' => $entity->uuid(),
          'rev' => $rev
        ),
        200
      );
    }
    catch (\Exception $e) {
      throw new HttpException(500, NULL, $e);
    }
  }
}