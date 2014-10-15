<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\UuidIndex;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @todo Don't extend EntityNormalizer. Follow the pattern of
 *   \Drupal\hal\Entity\Normalizer\ContentEntityNormalizer
 */
class AttachmentNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('\Drupal\file\FileInterface');

  /**
   * @var string[]
   * @todo Make this dynamic.
   */
  protected $format = array('stream', 'base64_stream');

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    /** @var \Drupal\file\FileInterface $data */
    $stream = fopen($data->getFileUri(), 'r');
    return $stream;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $meta_data = stream_get_meta_data($data);
    // @todo Use $class to instantiate the entity.
    return $this->entityManager->getStorage('file')->create(array('uri' => $meta_data['uri']));
  }

}
