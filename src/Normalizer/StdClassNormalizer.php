<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\StdClassNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class StdClassNormalizer extends NormalizerBase implements DenormalizerInterface {

  protected $supportedInterfaceOrClass = array('stdClass');

  /**
   * @var string
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    return $data;
  }

}
