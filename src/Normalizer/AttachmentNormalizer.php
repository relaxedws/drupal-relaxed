<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\UuidIndex;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @todo {@link https://www.drupal.org/node/2599920 Don't extend EntityNormalizer.}
 */
class AttachmentNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('\Drupal\file\FileInterface');

  /**
   * @var string[]
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
    $meta_data = is_resource($data) ? stream_get_meta_data($data) : NULL;
    // @todo {@link https://www.drupal.org/node/2599926 Use $class to instantiate the entity.}
    $file_data = array();
    if (isset($meta_data['uri'])) {
      $file_data['uri'] = $meta_data['uri'];
    }
    elseif (isset($context['uri'])) {
      $file_data['uri'] = $context['uri'];
    }

    $file_info_keys = array('uuid', 'status', 'uid');
    foreach ($file_info_keys as $key) {
      if (isset($context[$key])) {
        $file_data[$key] = $context[$key];
      }
    }
    return $this->entityManager->getStorage('file')->create($file_data);
  }

}
