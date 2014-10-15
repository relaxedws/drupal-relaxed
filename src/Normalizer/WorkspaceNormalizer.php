<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;

class WorkspaceNormalizer extends EntityNormalizer {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\multiversion\Entity\WorkspaceInterface');

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $context['entity_type'] = 'workspace';
    $data = parent::normalize($entity, $format, $context);

    if (isset($data['id'])) {
      $data['db_name'] = $data['id'];
      unset($data['id']);
    }
    if ($update_seq = $entity->getUpdateSeq()) {
      $data['update_seq'] = $update_seq;
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
