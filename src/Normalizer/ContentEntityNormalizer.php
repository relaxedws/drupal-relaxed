<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\UuidIndex;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @todo Don't extend EntityNormalizer. Follow the pattern of
 *   \Drupal\hal\Entity\Normalizer\ContentEntityNormalizer
 */
class ContentEntityNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndex $uuid_index
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndex $uuid_index) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = array();
    // New or mocked entities might not have a UUID yet.
    if ($entity->uuid()) {
      $data['_id'] = $entity->uuid();
    }
    $entity_type = $context['entity_type'] = $entity->getEntityTypeId();
    $data['_id'] = "$entity_type." . $data['_id'];

    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_revs_info->rev)) {
      $data['_rev'] = $entity->_revs_info->rev;
    }

    $field_definitions = $entity->getFieldDefinitions();
    foreach ($entity as $name => $field) {
      $field_type = $field_definitions[$name]->getType();
      $field_data = $this->serializer->normalize($field, $format, $context);
      // Add file and image field types into _attachments key.
      if ($field_type == 'file' || $field_type == 'image') {
        if (!isset($data['_attachments'])) {
          $data['_attachments'] = array();
        }
        if ($field_data !== NULL) {
          foreach ($field_data as $field_info) {
            $data['_attachments'] = array_merge($data['_attachments'], $field_info);
          }
        }
        continue;
      }
      if ($field_data !== NULL) {
        $data[$name] = $field_data;
      }
    }

    if (!empty($context['query']['revs'])) {
      $parts = explode('-', $entity->_revs_info->rev);
      $data['_revisions'] = array(
        'ids' => array(),
        'start' => (int) $parts[0],
      );
      foreach ($entity->_revs_info as $item) {
        $parts = explode('-', $item->rev);
        array_shift($parts);
        $data['_revisions']['ids'][] = implode('-', $parts);
      }
    }

    // Override the normalization for the _deleted special field, just so that we
    // follow the API spec.
    if (isset($entity->_deleted->value) && $entity->_deleted->value == TRUE) {
      $data['_deleted'] = TRUE;
    }
    elseif (isset($data['_deleted'])) {
      unset($data['_deleted']);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $entity_type_id = NULL;
    $entity_uuid = NULL;
    $entity_id = NULL;

    // Get the entity type and the entity id from $data['_id'] string.
    if (!empty($data['_id']) && strpos($data['_id'], '.') !== FALSE) {
      list($entity_type_from_data, $entity_id_from_data) = explode('.', $data['_id']);
    }
    elseif (!empty($data['_id']) && strpos($data['_id'], '/') !== FALSE) {
      list($entity_type_from_data, $entity_id_from_data) = explode('/', $data['_id']);
      if ($entity_type_from_data == '_local' && $entity_id_from_data) {
        $entity_type_from_data = 'replication_log';
      }
    }

    // Look for the entity type ID.
    if (!empty($context['entity_type'])) {
      $entity_type_id = $context['entity_type'];
    }
    elseif (isset($entity_type_from_data)) {
      $entity_type_id = $entity_type_from_data;
    }

    // Resolve the UUID.
    // @todo Needs test
    if (!empty($data['uuid'][0]['value']) && !isset($entity_id_from_data) && ($data['uuid'][0]['value'] != $entity_id_from_data)) {
      throw new UnexpectedValueException('The uuid and _id values does not match.');
    }
    if (!empty($data['uuid'][0]['value'])) {
      $entity_uuid = $data['uuid'][0]['value'];
    }
    elseif (isset($entity_id_from_data)) {
      $entity_uuid = $data['uuid'][0]['value'] = $entity_id_from_data;
    }
    // We need to nest the data for the _deleted field in its Drupal-specific
    // structure since it's un-nested to follow the API spec when normalized.
    if (isset($data['_deleted'])) {
      $data['_deleted'] = array(array('value' => $data['_deleted']));
    }

    // Map data from the UUID index.
    // @todo Needs test.
    if (!empty($entity_uuid)) {
      if ($record = $this->uuidIndex->get($entity_uuid)) {
        $entity_id = $record['entity_id'];
        if (empty($entity_type_id)) {
          $entity_type_id = $record['entity_type'];
        }
        elseif ($entity_type_id != $record['entity_type']) {
          throw new UnexpectedValueException('The entity_type value does not match the existing UUID record.');
        }
      }
    }

    if (empty($entity_type_id)) {
      throw new UnexpectedValueException('The entity_type value is missing.');
    }
    $entity_type = $this->entityManager->getDefinition($entity_type_id);

    if ($entity_id) {
      // @todo Needs test.
      $data[$entity_type->getKey('id')] = $entity_id;
    }
    // The bundle property behaves differently from other entity properties.
    // i.e. the nested structure with a 'value' key does not work.
    // @todo Does this still apply?
    if ($entity_type->hasKey('bundle')) {
      $bundle_key = $entity_type->getKey('bundle');
      if (!empty($data[$bundle_key][0]['value'])) {
        $type = $data[$bundle_key][0]['value'];
        $data[$bundle_key] = $type;
      }
    }

    // Denormalize File and Image field types.
    if (isset($data['_attachments'])) {
      foreach ($data['_attachments'] as $key => $value) {
        list($field_name, $delta, $file_uuid,,) = explode('/', $key);
        $file = \Drupal::entityManager()->loadEntityByUuid('file', $file_uuid);
        $data[$field_name][$delta] = array(
          'target_id' => $file->id(),
        );
      }
    }

    // Add _rev_info field info to the $data array.
    if (isset($data['_rev']) && isset($data['_revisions']['start']) && isset($data['_revisions']['ids'])) {
      $parts = explode('-', $data['_rev']);
      if ($parts[0] == $data['_revisions']['start'] && in_array($parts[1], $data['_revisions']['ids'])) {
        $data['_revs_info'][0]['rev'] = $data['_rev'];
      }
    }

    // Clean-up attributes we don't needs anymore.
    foreach (array('_id', '_rev', '_attachments', '_revisions') as $key) {
      if (isset($data[$key])) {
        unset($data[$key]);
      }
    }

    // @todo Move the below update logic to the resource plugin instead.
    $storage = $this->entityManager->getStorage($entity_type_id);

    if ($entity_id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->load($entity_id) ?: $storage->loadDeleted($entity_id);
      if ($entity) {
        foreach ($data as $name => $value) {
          $entity->{$name} = $value;
        }
      }
      elseif (isset($data['id'])) {
        unset($data['id']);
        $entity_id = NULL;
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $storage->create($data);
      }
    }
    else {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      // @todo Use the passed $class to instantiate the entity.
      $entity = $storage->create($data);
    }

    if ($entity_id) {
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(FALSE);
    }

    if (isset($context['query']['new_edits']) && ($context['query']['new_edits'] === FALSE)) {
      $entity->new_edits = FALSE;
    }

    return $entity;
  }
}
