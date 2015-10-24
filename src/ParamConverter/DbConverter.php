<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class DbConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Converts a UUID into an existing entity.
   *
   * @return string | \Drupal\Core\Entity\EntityInterface
   *   The entity if it exists in the database or else the original UUID string.
   * @todo {@link https://www.drupal.org/node/2600370 Fall back to a stub entity instead of UUID string.}
   */
  public function convert($entity_id, $definition, $name, array $defaults) {
    return $this->entityManager->getStorage('workspace')->load($entity_id) ?: $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($definition['type'] == 'relaxed:db');
  }
}
