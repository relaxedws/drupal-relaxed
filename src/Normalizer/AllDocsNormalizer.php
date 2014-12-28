<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

class AllDocsNormalizer extends NormalizerBase {

  protected $supportedInterfaceOrClass = array('Drupal\relaxed\AllDocs\AllDocsInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($all_docs, $format = NULL, array $context = array()) {
    $data = array(
      'offset' => 0,
      'rows' => array(),
    );

    /** @var \Drupal\relaxed\AllDocs\AllDocsInterface $all_docs */
    $rows = $all_docs->execute();

    foreach ($rows as $key => $value) {
      $data['rows'][] = array(
        'id' => $key,
        'key' => $key,
        'value' => $value
      );
    }

    $data['total_rows'] = count($rows);
    return $data;
  }
}
