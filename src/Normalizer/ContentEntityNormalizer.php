<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
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
   * @var \Drupal\multiversion\Entity\Index\UuidIndexInterface
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface
   */
  protected $revTree;

  /**
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndexInterface $uuid_index
   * @param \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface $rev_tree
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndexInterface $uuid_index, RevisionTreeIndexInterface $rev_tree, LinkManagerInterface $link_manager) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revTree = $rev_tree;
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $entity_type = $context['entity_type'] = $entity->getEntityTypeId();

    $data = array(
      '@context' => array(
        '_id' => '@id',
        $entity_type => $this->linkManager->getTypeUri($entity_type, $entity->bundle()),
      ),
      '@type' => $entity_type,
      '_id' => $entity->uuid()
    );

    $field_definitions = $entity->getFieldDefinitions();
    foreach ($entity as $name => $field) {
      $field_type = $field_definitions[$name]->getType();
      $field_data = $this->serializer->normalize($field, $format, $context);
      // Add file and image field types into _attachments key.
      if ($field_type == 'file' || $field_type == 'image') {
        if ($field_data !== NULL) {
          if (!isset($data['_attachments']) && !empty($field_data)) {
            $data['_attachments'] = array();
          }
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

    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_rev->value)) {
      $data['_rev'] = $entity->_rev->value;
    }

    // @todo: Needs test.
    if (!empty($context['query']['revs'])) {
      $entity_uuid = isset($data['uuid'][0]['value']) ? $data['uuid'][0]['value'] : NULL;

      // Build the default branch.
      $default_branch = array($entity->_rev->value => 'available');
      if ($entity_uuid) {
        $default_branch = $this->revTree->getDefaultBranch($entity_uuid);
      }

      // Build the revisions key.
      $ids = array();
      $start = 0;
      foreach ($default_branch as $rev => $status) {
        list($i, $hash) = explode('-', $rev);
        $ids[] = $hash;
        $start = (int) $i;
      }
      $data['_revisions'] = array(
        'ids' => array_reverse($ids),
        'start' => $start,
      );
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

    if (!empty($data['_id']) && strpos($data['_id'], '/') !== FALSE) {
      list($entity_type_from_data, $entity_uuid_from_data) = explode('/', $data['_id']);
      if ($entity_type_from_data == '_local' && $entity_uuid_from_data) {
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
    elseif (isset($data['@type'])) {
      $entity_type_id = $data['@type'];
    }

    // Resolve the UUID.
    // @todo Needs test
    if (!empty($data['uuid'][0]['value']) && !empty($data['_id']) && ($data['uuid'][0]['value'] != $data['_id'])) {
      throw new UnexpectedValueException('The uuid and _id values does not match.');
    }
    if (!empty($data['uuid'][0]['value'])) {
      $entity_uuid = $data['uuid'][0]['value'];
    }
    elseif (isset($entity_uuid_from_data)) {
      $entity_uuid = $data['uuid'][0]['value'] = $data['_id'];
    }
    // We need to nest the data for the _deleted field in its Drupal-specific
    // structure since it's un-nested to follow the API spec when normalized.
    // @todo Needs test for situation when a replication overwrites a delete.
    $deleted = isset($data['_deleted']) ? $data['_deleted'] : FALSE;
    $data['_deleted'] = array(array('value' => $deleted));

    // Map data from the UUID index.
    // @todo Needs test.
    if (!empty($entity_uuid)) {
      if ($record = $this->uuidIndex->get($entity_uuid)) {
        $entity_id = $record['entity_id'];
        if (empty($entity_type_id)) {
          $entity_type_id = $record['entity_type_id'];
        }
        elseif ($entity_type_id != $record['entity_type_id']) {
          throw new UnexpectedValueException('The entity_type value does not match the existing UUID record.');
        }
      }
    }

    if (empty($entity_type_id)) {
      throw new UnexpectedValueException('The entity_type value is missing.');
    }
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $bundle_key = $entity_type->getKey('bundle');

    if ($entity_id) {
      // @todo Needs test.
      $data[$id_key] = $entity_id;
    }

    if ($entity_type->hasKey('bundle')) {
      if (!empty($data[$bundle_key][0]['value'])) {
        // Add bundle info when entity is not new.
        $type = $data[$bundle_key][0]['value'];
        $data[$bundle_key] = $type;
      }
      elseif (!empty($data[$bundle_key][0]['target_id'])) {
        // Add bundle info when entity is new.
        $type = $data[$bundle_key][0]['target_id'];
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

    // Add the _rev field to the $data array.
    if (isset($data['_rev'])) {
      $data['_rev'] = array(array('value' => $data['_rev']));
    }
    if (isset($data['_revisions']['start']) && isset($data['_revisions']['ids'])) {
      $data['_rev'][0]['revisions'] = $data['_revisions']['ids'];
    }

    // Clean-up attributes we don't needs anymore.
    foreach (array('@context', '@type', '_id', '_attachments', '_revisions') as $key) {
      if (isset($data[$key])) {
        unset($data[$key]);
      }
    }

    // @todo Move the below update logic to the resource plugin instead.
    $storage = $this->entityManager->getStorage($entity_type_id);

    if ($entity_id) {
      if ($entity = $storage->load($entity_id) ?: $storage->loadDeleted($entity_id)) {
        foreach ($data as $name => $value) {
          if ($name == 'default_langcode') {
            continue;
          }
          $entity->{$name} = $value;
        }
      }
      elseif (isset($data[$id_key])) {
        unset($data[$id_key], $data[$revision_key]);
        $entity_id = NULL;
        $entity = $storage->create($data);
      }
    }
    else {
      $entity = NULL;
      // @todo Use the passed $class to instantiate the entity.
      if (!empty($bundle_key) && !empty($data[$bundle_key]) || $entity_type_id == 'replication_log') {
        unset($data[$id_key], $data[$revision_key]);
        $entity = $storage->create($data);
      }
    }

    if ($entity_id) {
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(FALSE);
    }

    return $entity;
  }

  /**
   * Constructs the entity URI.
   *
   * @param $entity
   *   The entity.
   *
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri($entity) {
    return $entity->url('canonical', array('absolute' => TRUE));
  }

}
