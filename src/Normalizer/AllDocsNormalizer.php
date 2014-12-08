<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class AllDocsNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $uuid = $data->uuid();
    $entity_type_id = $data->getEntityTypeId();
    return array(
      'id' => "$entity_type_id.$uuid",
      'key' => "$entity_type_id.$uuid",
      'value' => array(
        'rev' => $data->_revs_info->rev,
      ),
    );
  }
}
