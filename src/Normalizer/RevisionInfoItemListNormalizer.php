<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\ListNormalizer;

class RevisionInfoItemListNormalizer extends ListNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\multiversion\Plugin\Field\FieldType\RevisionInfoItemList');

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = array()) {
    if (!empty($context['revs_info'])) {
      return parent::normalize($field, $format, $context);
    }
  }
}
