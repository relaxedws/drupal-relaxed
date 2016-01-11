<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\ListNormalizer;

class RevisionInfoItemListNormalizer extends ListNormalizer {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\multiversion\Plugin\Field\FieldType\RevisionInfoItemList');

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = array()) {
    if (!empty($context['query']['revs_info'])) {
      return parent::normalize($field, $format, $context);
    }
    return [];
  }
}
