<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\FileItemNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Drupal\Component\Utility\Crypt;

class FileItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  protected $supportedInterfaceOrClass = array(
    'Drupal\file\Plugin\Field\FieldType\FileItem',
    'Drupal\image\Plugin\Field\FieldType\ImageItem',
  );

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    static $deltas = array();

    $definition = $data->getFieldDefinition();
    $values = $data->getValue();
    $file = entity_load('file', $values['target_id']);
    $uri = $file->getFileUri();
    $scheme = file_uri_scheme($uri);

    $field_name = $definition->getName();
    if (!isset($deltas[$field_name])) {
      $deltas[$field_name] = 0;
    }
    $key = $field_name . '/' . $deltas[$field_name] . '/' . $file->uuid() . '/' . $scheme . '/' . $file->getFileName();
    $deltas[$field_name]++;

    $file_contents = file_get_contents($uri);
    if (in_array(file_uri_scheme($uri), array('public', 'private')) == FALSE) {
      $file_data = '';
    }
    else {
      $file_data = base64_encode($file_contents);
    }

    // @todo Add 'revpos' value to the result array.
    $result = array(
      $key => array(
        'content_type' => $file->getMimeType(),
        'digest' => 'md5-' . base64_encode(md5($file_contents)),
        'length' => $file->getSize(),
        'data' => $file_data,
      ),
    );

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    return $data;
  }

}
