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
    return $rev_diff->getMissing();
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    /** @var \Drupal\relaxed\RevisionDiff\RevisionDiffInterface $rev_diff */
    if (isset($context['workspace'])) {
      $revs_diff = $class::createInstance(
        \Drupal::getContainer(),
        \Drupal::service('entity.index.rev'),
        $context['workspace']
      );
    }
    else {
      throw new LogicException('A \'workspace\' context is required for denormalizing revision diff data.');
    }
    $revs_diff->setRevisionIds($data);
    return $revs_diff;
  }

}
