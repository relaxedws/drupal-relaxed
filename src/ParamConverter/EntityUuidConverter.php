<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\Index\UuidIndex;
use Symfony\Component\Routing\Route;

class EntityUuidConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndex
   */
  protected $uuidIndex;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndex $uuid_index) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
  }

  /**
   * Converts a UUID into an existing entity.
   *
   * @return string | \Drupal\Core\Entity\EntityInterface
   *   The entity if it exists in the database or else the original UUID string.
   */
  public function convert($uuid, $definition, $name, array $defaults) {
    $entity_type_id = substr($definition['type'], strlen('entity_uuid:'));

    if (!$entity_type_id) {
      // If entity type ID is not provided, try to look it up the UUID index.
      if ($item = $this->uuidIndex->get($uuid)) {
        $entity_type_id = $item['entity_type_id'];
        $entity_id = $item['entity_id'];
        return $this->entityManager->getStorage($entity_type_id)->load($entity_id);
      }
      return $uuid;
    }
    return $this->entityManager->loadEntityByUuid($entity_type_id, $uuid) ?: $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity_uuid:') === 0) {
      return TRUE;
    }
    return FALSE;
  }
}
