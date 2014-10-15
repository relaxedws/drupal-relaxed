<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AttachmentResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\FileInterface;
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
 *     "canonical" = "Drupal\file\Entity\File",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}/{field_name}/{delta}/{file_uuid}/{scheme}/{filename}",
 *   },
 *   uri_parameters = {
 *     "canonical" = {
 *       "file_uuid" = "entity_uuid:file",
 *     }
 *   }
 * )
 */
class AttachmentResource extends ResourceBase {

  /**
   * @param $workspace
   * @param string | \Drupal\Core\Entity\EntityInterface[] $entities
   * @param string $field_name
   * @param integer $delta
   * @param string | \Drupal\file\Entity\File $file
   * @param string $scheme
   * @param string $filename
   * @return ResourceResponse
   */
  public function head($workspace, $entities, $field_name, $delta, $file, $scheme, $filename) {
    if (empty($entities) || is_string($entities) || empty($file) || is_string($file)) {
      throw new NotFoundHttpException();
    }
    // There can only be one entity for this endpoint.
    $entity = reset($entities);
    if (!$entity->access('view') || !$entity->{$field_name}->access('view')) {
      throw new AccessDeniedHttpException();
    }
    return new ResourceResponse(NULL, 200, $this->attachmentHeaders($file));
  }

  /**
   * @param $workspace
   * @param string | \Drupal\Core\Entity\EntityInterface[] $entities
   * @param string $field_name
   * @param integer $delta
   * @param string | \Drupal\file\Entity\File $file
   * @param string $scheme
   * @param string $filename
   * @return ResourceResponse
   */
  public function get($workspace, $entities, $field_name, $delta, $file, $scheme, $filename) {
    if (empty($entities) || is_string($entities) || empty($file) || is_string($file)) {
      throw new NotFoundHttpException();
    }
    // There can only be one entity for this endpoint.
    $entity = reset($entities);
    if (!$entity->access('view') || !$entity->{$field_name}->access('view')) {
      throw new AccessDeniedHttpException();
    }
    return new ResourceResponse($file, 200, $this->attachmentHeaders($file));
  }

  public function put($workspace, $existing_entity, $field_name, $delta, $file, $scheme, $filename, FileInterface $received_entity = NULL) {
    if (!$received_entity instanceof FileInterface) {
      throw new BadRequestHttpException(t('No content received'));
    }

    // We know there can only be one entity with PUT requests.
    $existing_entity = reset($existing_entity);

    // Check entity and field level access.
    if (!$existing_entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($existing_entity as $field_name => $field) {
      if (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
      }
    }

    // Validate the received data before saving.
    $this->validate($existing_entity);
    $this->validate($received_entity);
    try {
      $received_entity->save();

      // todo: Attach the new file to the entity.

      $existing_entity->save();
      $rev = $existing_entity->_revs_info->rev;
      $file_contents = file_get_contents($received_entity->getFileUri());
      $encoded_digest = base64_encode(md5($file_contents));

      return new ResourceResponse(
        array(
          'ok' => TRUE,
          'id' => $existing_entity->uuid(),
          'rev' => $rev,
        ),
        200,
        array(
          'X-Relaxed-ETag' => $encoded_digest,
          'Content-MD5' => $encoded_digest,
        )
      );
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, NULL, $e);
    }
  }

  public function delete($workspace, $entities, $field_name, $delta, $file, $scheme, $filename) {
    if (empty($entities) || is_string($entities) || empty($file) || is_string($file)) {
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

  /**
   * Helper method that returns the response headers for an attachment.
   *
   * @param \Drupal\file\FileInterface $file
   * @return array
   */
  protected function attachmentHeaders(FileInterface $file) {
    $file_contents = file_get_contents($file->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    return array(
      'X-Relaxed-ETag' => $encoded_digest,
      'Content-Type' => $file->getMimeType(),
      'Content-Length' => $file->getSize(),
      'Content-MD5' => $encoded_digest,
    );
  }
}
