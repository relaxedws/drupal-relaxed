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

    // Flatten some properties to follow the spec and make sure we have a NULL
    // value if there's no other value available.
    foreach (['session_id', 'source_last_seq'] as $field_name) {
      if (!empty($data[$field_name][0]['value'])) {
        $data[$field_name] = $data[$field_name][0]['value'];
      }
      else {
        $data[$field_name] = NULL;
      }
    }

    return $data;
  }

}
