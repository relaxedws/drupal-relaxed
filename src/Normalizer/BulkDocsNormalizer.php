<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BulkDocsNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $result = array();
    foreach ($data as $field) {
      $result[] = parent::normalize($field, $format, $context);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {

    foreach ($data as $field) {

      if (!empty($field['_entity_type'])) {
        $context['entity_type'] = $field['_entity_type'];
      }
      parent::denormalize($field, $class, $format, $context);
    }
  }

}