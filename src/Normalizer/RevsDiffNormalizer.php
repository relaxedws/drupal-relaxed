<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class RevsDiffNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\relaxed\RevisionDiff\RevisionDiffInterface');

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($rev_diff, $format = NULL, array $context = array()) {
    /** @var \Drupal\relaxed\RevisionDiff\RevisionDiffInterface $rev_diff */
    $missing = $rev_diff->getMissing();
    return $missing ?: new \stdClass();
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (!isset($context['workspace'])) {
      throw new LogicException('A \'workspace\' context is required to denormalize revision diff data.');
    }

    // @todo {@link https://www.drupal.org/node/2599930 Use injected container.}
    /** @var \Drupal\relaxed\RevisionDiff\RevisionDiffInterface $rev_diff */
    $revs_diff = $class::createInstance(
      \Drupal::getContainer(),
      \Drupal::service('entity.index.rev'),
      $context['workspace']
    );

    $revs_diff->setRevisionIds($data);
    return $revs_diff;
  }

}
