<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

class LinkItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\link\Plugin\Field\FieldType\LinkItem';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface|null
   */
  private $selectionManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface|null $selection_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SelectionPluginManagerInterface $selection_manager = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    foreach ($object->getProperties(TRUE) as $name => $field) {
      $attributes[$name] = $this->serializer->normalize($field, $format, $context);
    }

    // For some reasons the options field is not normalized correctly if it
    // has more information like attributes added by menu_attributes module.
    // The field data will be empty after normalization, so we add missing data
    // here.
    if (!empty($object->getValue()['options']) && empty($attributes['options'])) {
      $attributes['options'] = $object->getValue()['options'];
    }

    // Use the entity UUID instead of ID in urls like internal:/node/1.
    if (isset($attributes['uri'])) {
      $scheme = parse_url($attributes['uri'], PHP_URL_SCHEME);
      if (($scheme != 'internal' && $scheme != 'entity') ) {
        return $attributes;
      }
      $path = parse_url($attributes['uri'], PHP_URL_PATH);
      $path_arguments = explode('/', $path);
      if (isset($path[0]) && $path[0] == '/' && isset($path_arguments[1]) && isset($path_arguments[2]) && is_numeric($path_arguments[2]) && empty($path_arguments[3])) {
        $entity_type = $path_arguments[1];
        $entity_id = $path_arguments[2];
      }
      elseif (isset($path[0]) && $path[0] != '/' && isset($path_arguments[0]) && isset($path_arguments[1]) && is_numeric($path_arguments[1]) && empty($path_arguments[2])) {
        $entity_type = $path_arguments[0];
        $entity_id = $path_arguments[1];
      }
      else {
        return $attributes;
      }
      $entity_types = array_keys($this->entityTypeManager->getDefinitions());
      if (!in_array($entity_type, $entity_types)) {
        return $attributes;
      }
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      if ($entity instanceof EntityInterface) {
        $attributes['uri'] = ($scheme == 'entity') ? "$scheme:$entity_type/" . $entity->uuid() : "$scheme:/$entity_type/" . $entity->uuid();
        $bundle_key = $entity->getEntityType()->getKey('bundle');
        $bundle = $entity->bundle();
        if ($bundle_key && $bundle) {
          $attributes[$bundle_key] = $bundle;
        }
      }
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (isset($data['uri'])) {
      $scheme = parse_url($data['uri'], PHP_URL_SCHEME);
      if (($scheme != 'internal' && $scheme != 'entity') ) {
        return parent::denormalize($data, $class, $format, $context);
      }
      $path = parse_url($data['uri'], PHP_URL_PATH);
      $path_arguments = explode('/', $path);
      if (isset($path[0]) && $path[0] == '/' && isset($path_arguments[1]) && isset($path_arguments[2]) && empty($path_arguments[3])) {
        $entity_type = $path_arguments[1];
        $entity_uuid = $path_arguments[2];
      }
      elseif (isset($path[0]) && $path[0] != '/' && isset($path_arguments[0]) && isset($path_arguments[1]) && empty($path_arguments[2])) {
        $entity_type = $path_arguments[0];
        $entity_uuid = $path_arguments[1];
      }
      else {
        return parent::denormalize($data, $class, $format, $context);;
      }
      $entity_types = array_keys($this->entityTypeManager->getDefinitions());
      if (!in_array($entity_type, $entity_types)) {
        return parent::denormalize($data, $class, $format, $context);
      }
      $entities = $this->entityTypeManager->getStorage($entity_type)->loadByProperties(['uuid' => $entity_uuid]);
      $entity = reset($entities);
      if (!($entity instanceof EntityInterface)) {
        $bundle_key = $this->entityTypeManager->getStorage($entity_type)->getEntityType()->getKey('bundle');
        if (isset($data[$bundle_key])) {
          /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $selection_instance */
          $selection_instance = $this->selectionManager->getInstance(['target_type' => $entity_type]);
          // We use a temporary label and entity owner ID as this will be
          // backfilled later anyhow, when the real entity comes around.
          $entity = $selection_instance->createNewEntity($entity_type, $data[$bundle_key], rand(), 1);
          // Set the target workspace if we have it in context.
          if (isset($context['workspace'])
            && ($context['workspace'] instanceof WorkspaceInterface)
            && $entity->getEntityType()->get('workspace') !== FALSE) {
            $entity->workspace->target_id = $context['workspace']->id();
          }
          // Set the UUID to what we received to ensure it gets updated when
          // the full entity comes around later.
          $entity->uuid->value = $entity_uuid;
          // Indicate that this revision is a stub.
          $entity->_rev->is_stub = TRUE;
          $entity->save();
        }
      }
      if ($entity instanceof EntityInterface) {
        $data['uri'] = ($scheme == 'entity') ? "$scheme:$entity_type/" . $entity->id() : "$scheme:/$entity_type/" . $entity->id();
      }
    }

    return parent::denormalize($data, $class, $format, $context);
  }

  /**
   * Determines whether the item holds an unsaved entity.
   *
   * @param $data
   *
   * @return bool TRUE if the item holds an unsaved entity.
   */
  protected function hasNewEntity($data) {
    return $data['uri'] instanceof ContentEntityInterface && $data['uri']->isNew();
  }

}
