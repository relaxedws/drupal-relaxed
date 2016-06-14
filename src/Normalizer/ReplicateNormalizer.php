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

    if ($data['continuous'] == TRUE) {
      $this->replicate->setContinuous();
    }

    if ($data['cancel'] == TRUE) {
      $this->replicate->setCancel();
    }

    if ($data['create_target'] == TRUE) {
      $this->replicate->setCreateTarget();
    }

    if (is_array($data['doc_ids'])) {
      $this->replicate->setDocIds($data['doc_ids']);
    }
    
    return $this->replicate;
  }
  
}
