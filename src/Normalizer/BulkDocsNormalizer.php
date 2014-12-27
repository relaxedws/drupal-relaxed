<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\multiversion\Entity\Transaction\MockTransaction;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BulkDocsNormalizer extends NormalizerBase implements DenormalizerInterface {

  protected $supportedInterfaceOrClass = array('Drupal\relaxed\BulkDocs\BulkDocsInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($bulk_docs, $format = NULL, array $context = array()) {
    $data = array();
    /** @var \Drupal\relaxed\BulkDocs\BulkDocsInterface $bulk_docs */
    foreach ($bulk_docs->getResult() as $result) {
      $data[] = $result;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (!isset($context['workspace'])) {
      throw new LogicException('A \'workspace\' context is required to denormalize revision diff data.');
    }

    // @todo Use injected container.
    // @todo Use transactions when they can handle multiple entity types.
    /** @var \Drupal\relaxed\BulkDocs\BulkDocsInterface $bulk_docs */
    $bulk_docs = $class::createInstance(
      \Drupal::getContainer(),
      new MockTransaction(),
      \Drupal::service('workspace.manager'),
      $context['workspace']
    );

    if (
      (isset($data['new_edits']) && ($data['new_edits']) === FALSE) ||
      (isset($context['query']['new_edits']) && ($context['query']['new_edits']) === FALSE)
    ) {
      $bulk_docs->newEdits(FALSE);
    }

    $entities = array();
    if (isset($data['docs'])) {
      foreach ($data['docs'] as $doc) {
        if (!empty($doc)) {
          // @todo Find a more generic way to denormalize this w/o calling a
          // specific normalization service.
          $entities[] = \Drupal::service('relaxed.normalizer.content_entity')->denormalize($doc, 'Drupal\Core\Entity\ContentEntityInterface', $format, $context);
        }
      }
    }
    $bulk_docs->setEntities($entities);

    return $bulk_docs;
  }

}
