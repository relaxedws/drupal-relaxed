<?php

namespace Drupal\relaxed\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AttachmentNormalizer extends ContentEntityNormalizer implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('\Drupal\file\FileInterface');

  /**
   * @var string[]
   */
  protected $format = array('stream', 'base64_stream');

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    // If the 'new_revision_id' context is TRUE then normalize file entity as a
    // content entity not stream.
    if (!empty($context['new_revision_id'])) {
      return parent::normalize($data, $format, $context);
    }
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
