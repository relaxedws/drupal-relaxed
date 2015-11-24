<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\CommentItemNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CommentItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\comment\Plugin\Field\FieldType\CommentItem';

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = array()) {
    $value = $field->getValue();
    $field_info = [];
    $reference_fields = ['cid', 'last_comment_uid'];
    foreach ($value as $key => $item) {
      if (in_array($key, $reference_fields) && is_numeric($item)) {
        $field_info[$key] = NULL;
      }
      else {
        $field_info[$key] = $item;
      }
    }

    return $field_info;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    return $data;
  }

}
