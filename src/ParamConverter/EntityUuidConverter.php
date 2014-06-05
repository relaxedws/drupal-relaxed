<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\UuidIndex;
use Drupal\multiversion\Entity\RevisionIndex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class EntityUuidConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\UuidIndex
   */
  protected $uuidIndex;

  /**
   * @var string
   */
  protected $key = 'entity_uuid';

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndex $uuid_index, RevisionIndex $rev_index) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revIndex = $rev_index;
  }

  /**
   * Converts a UUID into an existing entity.
   *
   * @return string | \Drupal\Core\Entity\EntityInterface
   *   The entity if it exists in the database or else the original UUID string.
   */
  public function convert($uuid, $definition, $name, array $defaults, Request $request) {
    $entity_type_id = substr($definition['type'], strlen($this->key . ':'));
    $entity_id = NULL;
    $revision_id = NULL;

    // Figure out if we should load a specific revision or not.
    if (!$rev = $request->query->get('rev')) {
      $rev = $request->headers->get('if-none-match');
    }

    // Use the indices to resolve the entity and revision ID.
    if ($rev && $item = $this->revIndex->get("$uuid:$rev")) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
      $revision_id = $item['revision_id'];
    }
    elseif ($item = $this->uuidIndex->get($uuid)) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
    }

    // Return the plain UUID if we're missing information.
    if (!$entity_id || !$entity_type_id) {
      return $uuid;
    }

    $storage = $this->entityManager->getStorage($entity_type_id);
    if ($revision_id) {
      $entity = $storage->loadRevision($revision_id);
    }
    else {
      $entity = $storage->load($entity_id);
    }
    return !empty($entity) ? $entity : $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], $this->key) === 0) {
      return TRUE;
    }
    return FALSE;
  }
}
