<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class AllDocsNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = ['Drupal\relaxed\AllDocs\AllDocsInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($all_docs, $format = NULL, array $context = []) {
    $data = [
      'offset' => 0,
      'rows' => [],
    ];

    if (!empty($context['query']['include_docs'])) {
      $all_docs->includeDocs(TRUE);
    }

    /** @var \Drupal\relaxed\AllDocs\AllDocsInterface $all_docs */
    $rows = $all_docs->execute();

    foreach ($rows as $key => $value) {
      $data['rows'][] = [
        'id' => $key,
        'key' => $key,
        'value' => $value
      ];
    }

    $data['total_rows'] = count($rows);
    return $data;
  }
}
