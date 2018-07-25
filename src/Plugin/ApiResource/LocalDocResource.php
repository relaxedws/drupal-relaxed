<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @ApiResource(
 *   id = "local:doc",
 *   label = "Local document",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   path = "/{db}/_local/{docid}"
 * )
 */
class LocalDocResource extends DocApiResource {

  /**
   * @param string | \Drupal\workspaces\WorkspaceInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   */
  public function head($workspace, $existing) {
    if (!$workspace instanceof WorkspaceInterface || is_string($existing)) {
      throw new NotFoundHttpException('This 404 error is totally normal for ReplicationLog entities.');
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : [$existing];
    if ($revisions[0] instanceof ContentEntityInterface && !$revisions[0]->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only support local entity types.');
    }
    return parent::head($workspace, $revisions);
  }

  /**
   * @param string | \Drupal\workspaces\WorkspaceInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   */
  public function get($workspace, $existing) {
    if (!$workspace instanceof WorkspaceInterface || is_string($existing)) {
      throw new NotFoundHttpException('This 404 error is totally normal for ReplicationLog entities.');
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : [$existing];
    if ($revisions[0] instanceof ContentEntityInterface && !$revisions[0]->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only support local entity types.');
    }
    return parent::get($workspace, $revisions);
  }

  /**
   * @param string | \Drupal\workspaces\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity, Request $request) {
    if (!$received_entity->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only supports local entity types.');
    }
    return parent::put($workspace, $existing_entity, $received_entity, $request);
  }

}
