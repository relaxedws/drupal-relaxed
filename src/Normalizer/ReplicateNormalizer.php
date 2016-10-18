<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\relaxed\Replicate\Replicate;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ReplicateNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = ['Drupal\relaxed\Replicate\Replicate'];

  /**
   * @var \Drupal\relaxed\Replicate\Replicate
   */
  protected $replicate;

  /**
   * Constructor.
   *
   * @param \Drupal\relaxed\Replicate\Replicate $replicate
   */
  public function __construct(Replicate $replicate) {
    $this->replicate = $replicate;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($replicate, $format = NULL, array $context = []) {
    return $replicate->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $this->replicate->setSource($data['source']);
    $this->replicate->setTarget($data['target']);

    if (!empty($data['continuous'])) {
      $this->replicate->setContinuous();
    }

    if (!empty($data['cancel'])) {
      $this->replicate->setCancel();
    }

    if (!empty($data['create_target'])) {
      $this->replicate->setCreateTarget();
    }

    if (!empty($data['doc_ids']) && is_array($data['doc_ids'])) {
      $this->replicate->setDocIds($data['doc_ids']);
    }

    if (!empty($data['filter'])) {
      $this->replicate->setFilter($data['filter']);
    }

    if (!empty($data['parameters'])) {
      $this->replicate->setParameters($data['parameters']);
    }
    
    return $this->replicate;
  }
  
}
