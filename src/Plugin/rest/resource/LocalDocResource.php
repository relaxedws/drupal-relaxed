<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\Request;
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
 *   }
 * )
 */
class LocalDocResource extends DocResource {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function head($workspace, $existing) {
    if (is_string($workspace) || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : array($existing);

    if ($revisions[0] instanceof ContentEntityInterface && !$revisions[0]->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only support local entity types.');
    }

    return parent::head($workspace, $revisions);
  }

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace, $existing) {
    if (is_string($workspace) || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : array($existing);

    if ($revisions[0] instanceof ContentEntityInterface && !$revisions[0]->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only support local entity types.');
    }

    return parent::get($workspace, $revisions);
  }

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity, Request $request) {
    if (!$received_entity->getEntityType()->get('local')) {
      throw new BadRequestHttpException('This endpoint only support local entity types.');
    }
    return parent::put($workspace, $existing_entity, $received_entity, $request);
  }

}
