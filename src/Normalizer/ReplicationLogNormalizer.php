<?php

namespace Drupal\relaxed\Normalizer;

class ReplicationLogNormalizer extends ContentEntityNormalizer {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\relaxed\Entity\ReplicationLogInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);

    // Flatten some properties to follow the spec.
    foreach (['session_id', 'source_last_seq'] as $field_name) {
      if (isset($data[$field_name][0]['value'])) {
        $data[$field_name] = $data[$field_name][0]['value'];
      }
    }

    return $data;
  }

}
