<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\FileItemNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FileItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array(
    'Drupal\file\Plugin\Field\FieldType\FileItem',
    'Drupal\image\Plugin\Field\FieldType\ImageItem',
  );

  /**
   * @var string
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = array()) {
    $result = array();
    $definition = $data->getFieldDefinition();
    $values = $data->getValue();
    $file = isset($values['target_id']) ? entity_load('file', $values['target_id']) : NULL;
    if ($file) {
      $uri = $file->getFileUri();
      $scheme = file_uri_scheme($uri);
      $field_name = $definition->getName();

      // Create the attachment key, the format is: field_name/delta/uuid/scheme/filename.
      $key = $field_name . '/' . $data->getName() . '/' . $file->uuid() . '/' . $scheme . '/' . $file->getFileName();

      // @todo {@link https://www.drupal.org/node/2600354 Align file data normalization with attachment normalization.}
      $file_contents = file_get_contents($uri);
      if (in_array(file_uri_scheme($uri), array('public', 'private')) == FALSE) {
        $file_data = '';
      }
      else {
        $file_data = base64_encode($file_contents);
      }

      // @todo {@link https://www.drupal.org/node/2600360 Add revpos and other missing properties to the result array.}
      $result = array(
        $key => array(
          'content_type' => $file->getMimeType(),
          'digest' => 'md5-' . base64_encode(md5($file_contents)),
          'length' => $file->getSize(),
          'data' => $file_data,
        ),
      );
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    return $data;
  }

}
