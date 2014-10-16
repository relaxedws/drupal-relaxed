<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BulkDocsNormalizer extends ContentEntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $result = array();
    if (is_array($data) && isset($data['docs'])) {
      foreach ($data['docs'] as $field) {
        $result['docs'][] = parent::normalize($field, $format, $context);
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
    if (isset($data['new_edits']) && ($data['new_edits']) === FALSE) {
      $context['query']['new_edits'] = FALSE;
    }
    if (is_array($data) && isset($data['docs'])) {
      foreach ($data['docs'] as $field) {
        $result['docs'][] = parent::denormalize($field, $class, $format, $context);
      }
    }
    else {
      return parent::denormalize($data, $class, $format, $context);
    }

    return $result;
  }

}