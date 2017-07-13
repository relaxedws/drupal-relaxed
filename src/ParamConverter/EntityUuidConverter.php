<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\Index\UuidIndex;
use Symfony\Component\Routing\Route;

class EntityUuidConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndex
   */
  protected $uuidIndex;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndex $uuid_index
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UuidIndex $uuid_index) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuidIndex = $uuid_index;
  }

  /**
   * Converts a UUID into an existing entity.
   *
   * @param mixed $uuid
   *   The UUID value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
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
        return $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      }
      return $uuid;
    }
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadByProperties(['uuid' => $uuid]);
    return reset($entities) ?: $uuid;
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
