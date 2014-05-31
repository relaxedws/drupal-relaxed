<?php

namespace Drupal\couch_api\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;

class RepositoryNormalizer extends EntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\multiversion\Entity\RepositoryInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $context['entity_type'] = 'repository';
    $data = parent::normalize($entity, $format, $context);

    if (isset($data['name'])) {
      $data['db_name'] = $data['name'];
      unset($data['name']);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (isset($data['db_name'])) {
      $data['name'] = $data['db_name'];
      unset($data['db_name']);
    }
    return $this->entityManager->getStorage('repository')->create($data);
  }
}
