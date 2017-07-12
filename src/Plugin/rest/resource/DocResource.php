<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\FileInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\HttpMultipart\ResourceMultipartResponse;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\relaxed\HttpMultipart\Message\MultipartResponse as MultipartResponseParser;
use GuzzleHttp\Psr7;

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
 *   no_cache = TRUE
 * )
 *
 * @todo {@link https://www.drupal.org/node/2600428 Implement real ETag.}
 */
class DocResource extends ResourceBase {

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function head($workspace, $existing) {
    if (!$workspace instanceof WorkspaceInterface || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : [$existing];

    foreach ($revisions as $revision) {
      if (!$revision->access('view')) {
        throw new AccessDeniedHttpException();
      }
    }

    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse(NULL, 200, ['X-Relaxed-ETag' => $revisions[0]->_rev->value]);
  }

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace, $existing) {
    if (!$workspace instanceof WorkspaceInterface || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : [$existing];

    foreach ($revisions as $revision) {
      $entity_type_id = $revision->getEntityTypeId();
      $current_user = \Drupal::currentUser();
      if (($entity_type_id == 'user' && !$current_user->hasPermission('administer users'))
        || ($entity_type_id != 'user' && !$revision->access('view'))) {
        throw new AccessDeniedHttpException();
      }
      foreach ($revision as $field_name => $field) {
        if (!$field->access('view')) {
          unset($revision->{$field_name});
        }
      }
    }

    $result = $revisions[0];

    if (is_array($existing)) {
      $parts = [];
      $request = Request::createFromGlobals();
      // If not a JSON request then it's a request for multiple revisions.
      if ($request->headers->get('Accept') === 'multipart/mixed'
        || ($request->headers->get('Accept') === '*/*' && $request->headers->get('multipart') === 'mixed')) {
        foreach ($revisions as $revision) {
          $parts[] = new ResourceResponse($revision, 200, ['Content-Type' => 'application/json']);
        }
        return new ResourceMultipartResponse($parts, 200, ['Content-Type' => 'multipart/mixed']);
      }
      else {
        $result = [];
        foreach ($revisions as $revision) {
          $result[] = ['ok' => $revision];
        }
      }
    }

    // For replication_log entity type the result should contain info just about
    // one entity.
    if ($entity_type_id == 'replication_log') {
      $result = $revisions[0];
    }

    return new ResourceResponse($result, 200, ['X-Relaxed-ETag' => $revisions[0]->_rev->value]);
  }

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity, Request $request) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    // Check entity and field level access.
    if (!$received_entity->access('create')) {
      throw new AccessDeniedHttpException(t('Access denied when creating the entity.'));
    }
    foreach ($received_entity as $field_name => $field) {
      // @todo {@link https://www.drupal.org/node/2600438 Sanity check this.}
      if (!$field->access('create') && $field_name != 'pass') {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', ['@field' => $field_name]));
      }
    }

    // @todo {@link https://www.drupal.org/node/2600440 Ensure $received_entity
    // is saved with UUID from $existing_entity}

    if (!is_string($existing_entity) && $received_entity->_rev->value != $existing_entity->_rev->value) {
      throw new ConflictHttpException();
    }

    // Validate the received data before saving.
    $this->validate($received_entity);

    // Check if a requester wan't a new edit or not.
    if ($request->get('new_edits') == 'false') {
      $received_entity->_rev->new_edit = FALSE;
    }

    try {
      $received_entity->save();
      $rev = $received_entity->_rev->value;
      $data = ['ok' => TRUE, 'id' => $received_entity->uuid(), 'rev' => $rev];
      return new ResourceResponse($data, 201, ['X-Relaxed-ETag' => $rev]);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, $e->getMessage());
    }
  }

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function delete($workspace, $entity) {
    if (!($workspace instanceof WorkspaceInterface)
      || !($entity instanceof ContentEntityInterface)) {
      throw new NotFoundHttpException();
    }

    if (!$entity->access('delete')) {
      throw new AccessDeniedHttpException();
    }

    $record = \Drupal::service('multiversion.entity_index.uuid')->get($entity->uuid());
    $last_rev = $record['rev'];
    if ($last_rev != $entity->_rev->value) {
      throw new ConflictHttpException();
    }

    try {
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new HttpException(500, NULL, $e);
    }

    return new ResourceResponse(['ok' => TRUE], 200);
  }

}
