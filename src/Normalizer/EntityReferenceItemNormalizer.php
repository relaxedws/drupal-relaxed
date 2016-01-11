<?php

/**
 * @file
 * Contains \Drupal\relaxed\Normalizer\EntityReferenceItemNormalizer.
 */

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EntityReferenceItemNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = array()) {
    $value = $field->getValue();
    $target_type = $field->getFieldDefinition()->getSetting('target_type');
    $storage = \Drupal::entityManager()->getStorage($target_type);

    if (!($storage instanceof FieldableEntityStorageInterface)) {
      return $value;
    }

    $taget_id = isset($value['target_id']) ? $value['target_id'] : NULL;
    if (!$taget_id) {
      return $value;
    }

    $referenced_entity = entity_load($target_type, $taget_id, TRUE);
    if (!$referenced_entity) {
      return $value;
    }

    $field_info = [
      'entity_type_id' => $target_type,
      'target_uuid' => $referenced_entity->uuid(),
    ];

    $bundle_key = $referenced_entity->getEntityType()->getKey('bundle');
    $bundle = $referenced_entity->bundle();
    if ($bundle_key && $bundle) {
      $field_info[$bundle_key] = $bundle;
    }

    return $field_info;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    return $data;
  }

}
