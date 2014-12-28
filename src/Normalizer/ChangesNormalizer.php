<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\ChangesNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class ChangesNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = array('Drupal\relaxed\Changes\ChangesInterface');

  /**
   * @var string
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($changes, $format = NULL, array $context = array()) {
    /** @var \Drupal\relaxed\Changes\ChangesInterface $changes */
    $results = $changes->getNormal();
    $last_result = end($results);
    $last_seq = isset($last_result['seq']) ? $last_result['seq'] : 0;

    return array(
      'last_seq' => $last_seq,
      'results' => $results,
    );
  }

}
