<?php

namespace Drupal\relaxed\Normalizer;

class BulkDocsNormalizer extends ContentEntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

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
