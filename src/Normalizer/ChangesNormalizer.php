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

    // 'since' parameter is important for PouchDB replication.
    $since = (isset($context['query']['since']) && is_numeric($context['query']['since'])) ? $context['query']['since'] : 0;

    $filtered_results = array();
    if ($since == 0) {
      $filtered_results = $results;
    }
    else {
      foreach ($results as $result) {
        if ($result['seq'] > $since) {
          $filtered_results[] = $result;
        }
      }
    }

    return array(
      'last_seq' => $last_seq,
      'results' => $filtered_results,
    );
  }

}
