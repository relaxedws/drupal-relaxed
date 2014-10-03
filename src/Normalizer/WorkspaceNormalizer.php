<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;

class WorkspaceNormalizer extends EntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\multiversion\Entity\WorkspaceInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $context['entity_type'] = 'workspace';
    $data = parent::normalize($entity, $format, $context);

    if (isset($data['name'])) {
      $data['db_name'] = $data['id'];
      unset($data['id']);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (isset($data['db_name'])) {
      $data['id'] = $data['db_name'];
      unset($data['db_name']);
    }
    return $this->entityManager->getStorage('workspace')->create($data);
  }
}
