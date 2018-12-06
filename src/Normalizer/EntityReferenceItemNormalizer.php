<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\multiversion\EntityReferenceItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

class EntityReferenceItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = [EntityReferenceItem::class];

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []) {
    $value = $field->getValue();
    $target_type = $field->getFieldDefinition()->getSetting('target_type');
    $storage = \Drupal::entityTypeManager()->getStorage($target_type);

    if (!($storage instanceof FieldableEntityStorageInterface)) {
      return $value;
    }

    $taget_id = isset($value['target_id']) ? $value['target_id'] : NULL;
    // For user target type use the ID from multiversion configuration object.
    if ($target_type === 'user') {
      $taget_id = \Drupal::service('relaxed.users_mapping')->getUidFromConfig();
    }
    if ($taget_id === NULL) {
      return $value;
    }

    $referenced_entity = $storage->load($taget_id);
    if (!$referenced_entity) {
      return $value;
    }

    $field_info = [
      'entity_type_id' => $target_type,
      'target_uuid' => $referenced_entity->uuid(),
    ];

    // Add username to the field info for user entity type.
    if ($target_type === 'user' && $username = $referenced_entity->getUsername()) {
      $field_info['username'] = $username;
    }

    if ($target_type === 'file') {
      $file_info = $value;
      unset($file_info['target_id']);
      $field_info += $file_info;
    }

    $bundle_key = $referenced_entity->getEntityType()->getKey('bundle');
    $bundle = $referenced_entity->bundle();
    if ($bundle_key && $bundle) {
      $field_info[$bundle_key] = $bundle;
    }

    return $field_info;
  }

}
