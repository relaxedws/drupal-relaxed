<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\replication\RevisionDiffFactoryInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class RevsDiffNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\replication\RevisionDiff\RevisionDiffInterface');

  /**
   * @var string[]
   */
  protected $format = array('json');

  /** @var  \Drupal\replication\RevisionDiffFactoryInterface */
  protected $revisionDiffFactory;

  public function __construct(RevisionDiffFactoryInterface $revisiondiff_factory) {
    $this->revisionDiffFactory = $revisiondiff_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($rev_diff, $format = NULL, array $context = array()) {
    /** @var \Drupal\replication\RevisionDiff\RevisionDiffInterface $rev_diff */
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

    return $this->revisionDiffFactory->get($context['workspace'])->setRevisionIds($data);
  }

}
