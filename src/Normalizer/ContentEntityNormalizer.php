<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\file\FileInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

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
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndexInterface $uuid_index
   * @param \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface $rev_tree
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndexInterface $uuid_index, RevisionTreeIndexInterface $rev_tree, LinkManagerInterface $link_manager, LanguageManagerInterface $language_manager, SelectionPluginManagerInterface $selection_manager = NULL) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revTree = $rev_tree;
    $this->linkManager = $link_manager;
    $this->languageManager = $language_manager;
    $this->selectionManager = $selection_manager;
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
    $entity_default_language = $entity->language();
    $entity_languages = $entity->getTranslationLanguages();

    $data = array(
      '@context' => array(
        '_id' => '@id',
        $entity_type_id => $this->linkManager->getTypeUri($entity_type_id, $entity->bundle()),
        '@language' => $entity_default_language->getId(),
      ),
      '@type' => $entity_type_id,
      '_id' => $entity_uuid,
    );

    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_rev->value)) {
      $data['_rev'] = $entity->_rev->value;
    }

    $field_definitions = $entity->getFieldDefinitions();
    foreach ($entity_languages as $entity_language) {
      $translation = $entity->getTranslation($entity_language->getId());
      $data[$entity_language->getId()] =
        [
          '@context' => [
            '@language' => $entity_language->getId(),
          ]
        ];
      foreach ($translation as $name => $field) {
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
          $data[$entity_language->getId()][$name] = $items;
        }
      }
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
    $default_language = $data['@context']['@language'];
    $site_languages = $this->languageManager->getLanguages();

    // Resolve the entity type ID.
    if (isset($data['@type'])) {
      $entity_type_id = $data['@type'];
    }
    elseif (!empty($context['entity_type'])) {
      $entity_type_id = $context['entity_type'];
    }

    // Resolve the UUID.
    if (empty($entity_uuid) && !empty($data['_id'])) {
      $entity_uuid = $data[$default_language]['uuid'][0]['value'] = $data['_id'];
    }

    // We need to nest the data for the _deleted field in its Drupal-specific
    // structure since it's un-nested to follow the API spec when normalized.
    // @todo {@link https://www.drupal.org/node/2599938 Needs test for situation when a replication overwrites delete.}
    //$deleted = isset($data['_deleted']) ? $data['_deleted'] : FALSE;
    //$data['_deleted'] = array(array('value' => $deleted));

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

    $bundle_id = $entity_type_id;
    if ($entity_type->hasKey('bundle')) {
      foreach ($site_languages as $site_language) {
        $langcode = $site_language->getId();
        if (!empty($data[$langcode][$bundle_key][0]['value'])) {
          // Add bundle info when entity is not new.
          $bundle_id = $data[$langcode][$bundle_key][0]['value'];
          $data[$langcode][$bundle_key] = $bundle_id;
        }
        elseif (!empty($data[$langcode][$bundle_key][0]['target_id'])) {
          // Add bundle info when entity is new.
          $bundle_id = $data[$langcode][$bundle_key][0]['target_id'];
          $data[$langcode][$bundle_key] = $bundle_id;
        }
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
            $data[$field_name][$delta] = [
              'target_id' => NULL,
              'entity' => $file,
            ];
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
    // Remove changed info, otherwise we can get validation errors when the
    // 'changed' value for existing entity is higher than for the new entity (revision).
    // @see \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator::validate().
    foreach (array('@context', '@type', '_id', '_attachments', '_revisions', 'changed') as $key) {
      if (isset($data[$key])) {
        unset($data[$key]);
      }
      foreach ($site_languages as $site_language) {
        $langcode = $site_language->getId();
        if (isset($data[$langcode][$key])) {
          unset($data[$langcode][$key]);
        }
      }
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

    // Exclude "name" field (the user name) for comment entity type because
    // we'll change it during replication if it's a duplicate.
    if ($entity_type_id == 'comment') {
      foreach ($site_languages as $site_language) {
        $langcode = $site_language->getId();
        if (isset($data[$langcode]['name'])) {
          unset($data[$langcode]['name']);
        }
      }
    }

    // @todo {@link https://www.drupal.org/node/2599946 Move the below update
    // logic to the resource plugin instead.}
    $storage = $this->entityManager->getStorage($entity_type_id);

    // Denormalize entity reference fields.
    foreach ($site_languages as $site_language) {
      $langcode = $site_language->getId();
      foreach ($data[$langcode] as $field_name => $field_info) {
        if (!is_array($field_info)) {
          continue;
        }
        foreach ($field_info as $delta => $item) {
          if (isset($item['target_uuid'])) {
            $fields = $this->entityManager->getFieldDefinitions($entity_type_id, $bundle_id);
            // Figure out what bundle we should use when creating the stub.
            $settings = $fields[$field_name]->getSettings();

            // Find the target entity type and target bundle IDs and figure out if
            // the referenced entity exists or not.
            $target_entity_uuid = $item['target_uuid'];
            $target_entity_type_id = $settings['target_type'];

            if (isset($settings['handler_settings']['target_bundles'])) {
              $target_bundle_id = reset($settings['handler_settings']['target_bundles']);
            }
            else {
              // @todo: Update when {@link https://www.drupal.org/node/2412569
              // this setting is configurable}.
              $bundles = $this->entityManager->getBundleInfo($target_entity_type_id);
              $target_bundle_id = key($bundles);
            }
            $target_storage = $this->entityManager->getStorage($target_entity_type_id);
            $target_entity = $target_storage->loadByProperties(['uuid' => $target_entity_uuid]);
            $target_entity = !empty($target_entity) ? reset($target_entity) : NULL;

            if ($target_entity) {
              $data[$field_name][$delta] = array(
                'target_id' => $target_entity->id(),
              );
            }
            // If the target entity doesn't exist we need to create a stub entity
            // in its place to ensure that the replication continues to work.
            // The stub entity will be updated when it's full entity comes around
            // later in the replication.
            else {
              $options = [
                'target_type' => $target_entity_type_id,
                'handler_settings' => $settings['handler_settings'],
              ];
              /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $selection_instance */
              $selection_instance = $this->selectionManager->getInstance($options);
              // We use a temporary label and entity owner ID as this will be
              // backfilled later anyhow, when the real entity comes around.
              $target_entity = $selection_instance
                ->createNewEntity($target_entity_type_id, $target_bundle_id, rand(), 1);

              // Set the UUID to what we received to ensure it gets updated when
              // the full entity comes around later.
              $target_entity->uuid->value = $target_entity_uuid;
              // Indicate that this revision is a stub.
              $target_entity->_rev->is_stub = TRUE;

              // Populate the data field.
              $data[$langcode][$field_name][$delta] = array(
                'target_id' => NULL,
                'entity' => $target_entity,
              );
            }
          }
        }
      }
    }

    // @todo {@link https://www.drupal.org/node/2599926 Use the passed $class to instantiate the entity.}

    if ($entity_id) {
      if ($entity = $storage->load($entity_id) ?: $storage->loadDeleted($entity_id)) {
        if (!empty($data[$entity->language()])) {
          foreach ($data[$entity->language()] as $name => $value) {
            if ($name == 'default_langcode') {
              continue;
            }
            $entity->{$name} = $value;
          }
        }
      }
      elseif (isset($data[$default_language][$id_key])) {
        unset($data[$id_key], $data[$revision_key]);
        $entity_id = NULL;
        $entity = $storage->create($data[$default_language]);
      }

      foreach ($site_languages as $site_language) {
        $langcode = $site_language->getId();
        if ($entity->language() != $langcode) {
          $entity->addTranslation($langcode, $data[$langcode]);
        }
      }
    }
    else {
      $entity = NULL;
      $entity_types_to_create = ['user'];
      if (!empty($bundle_key) && !empty($data[$bundle_key]) || in_array($entity_type_id, $entity_types_to_create)) {
        unset($data[$id_key], $data[$revision_key]);
        $entity = $storage->create($data[$default_language]);
      }
    }

    if ($entity_id) {
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(FALSE);
      $entity->_rev->is_stub = FALSE;
    }

    Cache::invalidateTags(array($entity_type_id . '_list'));

    return $entity;
  }

}
