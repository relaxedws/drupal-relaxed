<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Converts TextItem fields to an array including computed values.
 */
class TextItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\text\Plugin\Field\FieldType\TextItemBase';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    foreach ($object->getProperties(TRUE) as $name => $field) {
      $attributes[$name] = $this->serializer->normalize($field, $format, $context);
    }
    return $attributes;
  }

}
