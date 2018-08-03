<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class ChangesNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = ['Drupal\relaxed\Changes\ChangesInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($changes, $format = NULL, array $context = []) {
    /** @var \Drupal\relaxed\Changes\ChangesInterface $changes */
    if (isset($context['query']['filter'])) {
      $changes->filter($context['query']['filter']);
    }
    if (isset($context['query']['parameters'])) {
      $changes->parameters($context['query']['parameters']);
    }
    if (isset($context['query']['limit'])) {
      $changes->setLimit($context['query']['limit']);
    }
    $since = (isset($context['query']['since']) && is_numeric($context['query']['since'])) ? $context['query']['since'] : 0;
    $changes->setSince($since);

    $results = $changes->getNormal();
    $last_result = end($results);
    $last_seq = isset($last_result['seq']) ? $last_result['seq'] : 0;

    return [
      'last_seq' => $last_seq,
      'results' => $results,
    ];
  }

}
