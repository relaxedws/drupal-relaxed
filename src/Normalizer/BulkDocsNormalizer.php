<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BulkDocsNormalizer extends ContentEntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * @param EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $result = array();
    if (is_array($data)) {
      foreach ($data as $field) {
        $result[] = parent::normalize($field, $format, $context);
      }
    }
    else {
      return parent::normalize($data, $format, $context);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $result = array();
    if (is_array($data) && !isset($data['uuid'])) {
      foreach ($data as $field) {
        $result[] = parent::denormalize($field, $class, $format, $context);
      }
    }
    else {
      return parent::denormalize($data, $class, $format, $context);
    }

    return $result;
  }

}