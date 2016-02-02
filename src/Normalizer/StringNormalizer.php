<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\StringNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\filter\Entity\FilterFormat;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 *
 */
class StringNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_string($data);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $filter_formats = FilterFormat::loadMultiple();
    $filtered = [];
    foreach ($filter_formats as $filter_format) {
      $format = $filter_format->get('format');
      $filtered[$format] = check_markup($data, $format);
    }
    return $filtered;
  }

}
