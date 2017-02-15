<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class ChangesNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = ['Drupal\replication\Changes\ChangesInterface'];

  /**
   * @var string
   */
  protected $format = ['json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($changes, $format = NULL, array $context = []) {
    /** @var \Drupal\replication\Changes\ChangesInterface $changes */
    if (isset($context['query']['filter'])) {
      $changes->filter($context['query']['filter']);
    }
    if (isset($context['query']['parameters'])) {
      $changes->parameters($context['query']['parameters']);
    }
    $results = $changes->getNormal();
    $last_result = end($results);
    $last_seq = isset($last_result['seq']) ? $last_result['seq'] : 0;

    // 'since' parameter is important for PouchDB replication.
    $since = (isset($context['query']['since']) && is_numeric($context['query']['since'])) ? $context['query']['since'] : 0;

    $filtered_results = [];
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

    return [
      'last_seq' => $last_seq,
      'results' => $filtered_results,
    ];
  }

}
