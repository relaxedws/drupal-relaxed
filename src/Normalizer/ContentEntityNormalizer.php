<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\file\FileInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @todo {@link https://www.drupal.org/node/2599920 Don't extend EntityNormalizer.}
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
    $entity_type_id = $context['entity_type'] = $entity->getEntityTypeId();
    $entity_type = $this->entityManager->getDefinition($entity_type_id);

    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $uuid_key = $entity_type->getKey('uuid');

    $entity_uuid = $entity->uuid();

    $data = array(
      '@context' => array(
        '_id' => '@id',
        $entity_type_id => $this->linkManager->getTypeUri($entity_type_id, $entity->bundle()),
      ),
      '@type' => $entity_type_id,
      '_id' => $entity_uuid,
    );

    $field_definitions = $entity->getFieldDefinitions();
    foreach ($entity as $name => $field) {
      $field_type = $field_definitions[$name]->getType();
      $items = $this->serializer->normalize($field, $format, $context);
      // Add file and image field types into _attachments key.
      if ($field_type == 'file' || $field_type == 'image') {
        if ($items !== NULL) {
          if (!isset($data['_attachments']) && !empty($items)) {
            $data['_attachments'] = array();
          }
          foreach ($items as $item) {
            $data['_attachments'] = array_merge($data['_attachments'], $item);
          }
        }
        continue;
      }
      if ($field_type == 'password') {
        continue;
      }

      if ($items !== NULL) {
        $data[$name] = $items;
      }
    }

    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_rev->value)) {
      $data['_rev'] = $entity->_rev->value;
    }

    // @todo: {@link https://www.drupal.org/node/2599938 Needs test.}
    if (!empty($context['query']['revs']) || !empty($context['query']['revs_info'])) {
      $default_branch = $this->revTree->getDefaultBranch($entity_uuid);

      $i = 0;
      foreach (array_reverse($default_branch) as $rev => $status) {
        // Build data for _revs_info.
        if (!empty($context['query']['revs_info'])) {
          $data['_revs_info'][] = array('rev' => $rev, 'status' => $status);
        }
        if (!empty($context['query']['revs'])) {
          list($start, $hash) = explode('-', $rev);
          $data['_revisions']['ids'][] = $hash;
          if ($i == 0) {
            $data['_revisions']['start'] = (int) $start;
          }
        }
        $i++;
      }
    }

    if (!empty($context['query']['conflicts'])) {
      $conflicts = $this->revTree->getConflicts($entity_uuid);
      foreach ($conflicts as $rev => $status) {
        $data['_conflicts'][] = $rev;
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

    // Finally we remove certain fields that are "local" to this host.
    unset($data['workspace'], $data[$id_key], $data[$revision_key], $data[$uuid_key]);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $entity_type_id = NULL;
    $entity_uuid = NULL;
    $entity_id = NULL;

    // Resolve the entity type ID.
    if (isset($data['@type'])) {
      $entity_type_id = $data['@type'];
    }
    elseif (!empty($context['entity_type'])) {
      $entity_type_id = $context['entity_type'];
    }
    elseif (!empty($data['_id']) && strpos($data['_id'], '/') !== FALSE) {
      list($prefix, $entity_uuid) = explode('/', $data['_id']);
      if ($prefix == '_local' && $entity_uuid) {
        $entity_type_id = 'replication_log';
        $data['uuid'] = $entity_uuid;
      }
    }

    // Resolve the UUID.
    if (empty($entity_uuid) && !empty($data['_id'])) {
      $entity_uuid = $data['uuid'][0]['value'] = $data['_id'];
    }

    // We need to nest the data for the _deleted field in its Drupal-specific
    // structure since it's un-nested to follow the API spec when normalized.
    // @todo {@link https://www.drupal.org/node/2599938 Needs test for situation when a replication overwrites delete.}
    $deleted = isset($data['_deleted']) ? $data['_deleted'] : FALSE;
    $data['_deleted'] = array(array('value' => $deleted));

    // Map data from the UUID index.
    // @todo: {@link https://www.drupal.org/node/2599938 Needs test.}
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
    if (empty($entity_uuid)) {
      throw new UnexpectedValueException('The uuid value is missing.');
    }

    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $bundle_key = $entity_type->getKey('bundle');

    if ($entity_id) {
      // @todo {@link https://www.drupal.org/node/2599938 Needs test.}
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
        list($field_name, $delta, $file_uuid, $scheme, $filename) = explode('/', $key);
        $uri = "$scheme://$filename";
        // Check if exists a file with this uuid.
        $file = $this->entityManager->loadEntityByUuid('file', $file_uuid);
        if (!$file) {
          // Check if exists a file with this $uri, if it exists then
          // change the URI and save the new file.
          $existing_files = entity_load_multiple_by_properties('file', array('uri' => $uri));
          if (count($existing_files)) {
            $uri = file_destination($uri, FILE_EXISTS_RENAME);
          }
          $file_context = array(
            'uri' => $uri,
            'uuid' => $file_uuid,
            'status' => FILE_STATUS_PERMANENT,
            'uid' => \Drupal::currentUser()->id(),
          );
          $file = \Drupal::getContainer()->get('serializer')->deserialize($value['data'], '\Drupal\file\FileInterface', 'base64_stream', $file_context);
          if ($file instanceof FileInterface) {
            $data[$field_name][$delta] = array('entity_to_save' => $file);
          }
          continue;
        }
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

    // Remove changed info, otherwise we can get validation errors when the
    // 'changed' value for existing entity is higher than for the new entity (revision).
    // @see \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator::validate().
    if (isset($data['changed'])) {
      unset($data['changed']);
    }

    // For the user entity type set a random name if an user with the same name
    // already exists in the database.
    if ($entity_type_id == 'user') {
      $query = db_select('users', 'u');
      $query->fields('u', ['uuid']);
      $query->join('users_field_data', 'ufd', 'u.uid = ufd.uid');
      $query->fields('ufd', ['name']);
      $existing_users_names = $query->execute()->fetchAllKeyed(1, 0);
      $random = new Random();
      $name = $data['name'][0]['value'];
      if (!empty($name) && in_array($name, array_keys($existing_users_names)) && $existing_users_names[$name] != $entity_uuid) {
        $data['name'][0]['value'] = $name . '_' . $random->name(8, TRUE);
      }
      elseif (empty($name)) {
        $data['name'][0]['value'] = 'anonymous_' . $random->name(8, TRUE);
      }
    }

    // Remove changed info, otherwise we can get validation errors.
    if (isset($data['changed'])) {
      unset($data['changed']);
    }

    // Exclude "name" field (the user name) for comment entity type because
    // we'll change it during replication if it's a duplicate.
    if ($entity_type_id == 'comment' && isset($data['name'])) {
      unset($data['name']);
    }

    // @todo {@link https://www.drupal.org/node/2599946 Move the below update logic to the resource plugin instead.}
    $storage = $this->entityManager->getStorage($entity_type_id);

    // Denormalize entity reference fields.
    foreach ($data as $field_name => $field_info) {
      if (!is_array($field_info)) {
        continue;
      }
      foreach ($field_info as $delta => $item) {
        if (isset($item['entity_type_id']) && isset($item['target_uuid'])) {
          $target_storage = $this->entityManager->getStorage($item['entity_type_id']);
          $target_entity = $target_storage->loadByProperties(array('uuid' => $item['target_uuid']));
          $target_entity = !empty($target_entity) ? reset($target_entity) : NULL;
          if ($target_entity) {
            $data[$field_name][$delta] = array(
              'target_id' => $target_entity->id(),
            );
            continue;
          }
          $target_entity_values = array('uuid' => $item['target_uuid']);

          // Let other modules feedback about their own additions.
          $target_entity_values = array_merge($target_entity_values, \Drupal::moduleHandler()->invokeAll('entity_create_stub', array($target_storage)));


          $target_entity = entity_create($item['entity_type_id'], $target_entity_values);
          $data[$field_name][$delta] = array(
            'entity_to_save' => $target_entity,
          );
        }
      }
    }

    if ($entity_id) {
      if ($entity = $storage->load($entity_id) ?: $storage->loadDeleted($entity_id)) {
        foreach ($data as $name => $value) {
          if ($name == 'default_langcode') {
            continue;
          }
          if (strpos($entity->_rev->value, '1-101010101010101010101010') !== FALSE) {
            $entity->{$name} = $value;
            $entity->_rev->is_stub = TRUE;
          }
          else {
            $entity->{$name} = $value;
          }
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
      $entity_types_to_create = ['replication_log', 'user'];
      // @todo {https://www.drupal.org/node/2599926 Use the passed $class to instantiate the entity.}
      if (!empty($bundle_key) && !empty($data[$bundle_key]) || in_array($entity_type_id, $entity_types_to_create)) {
        unset($data[$id_key], $data[$revision_key]);
        $entity = $storage->create($data);
      }
    }

    if ($entity_id) {
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(FALSE);
    }

    Cache::invalidateTags(array($entity_type_id . '_list'));

    return $entity;
  }

}
