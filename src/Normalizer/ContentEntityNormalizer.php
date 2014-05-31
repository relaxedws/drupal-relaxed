<?php

namespace Drupal\couch_api\Normalizer;

use Drupal\serialization\Normalizer\EntityNormalizer;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * @todo Don't extend EntityNormalizer. Follow the pattern of
 *   \Drupal\hal\Entity\Normalizer\ContentEntityNormalizer
 */
class ContentEntityNormalizer extends EntityNormalizer {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);
    // New or mocked entities might not have a UUID yet.
    if (!empty($entity->uuid())) {
      $data['_id'] = $entity->uuid();
    }
    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_revs_info->rev)) {
      $data['_rev'] = $entity->_revs_info->rev;
    }
    $data['_entity_type'] = $entity->getEntityTypeId();

    if (empty($context['revs_info']) && isset($data['_revs_info'])) {
      unset($data['_revs_info']);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $entity_type_id = NULL;
    // Look for the entity type ID.
    if (!empty($context['entity_type'])) {
      $entity_type_id = $context['entity_type'];
    }
    elseif (!empty($data['_entity_type'])) {
      $entity_type_id = $data['_entity_type'];
    }

    // @todo When we depend on schemaless.module we should be graceful and
    // fall back to schemaless_doc entity type.
    if (empty($entity_type_id)) {
      throw new UnexpectedValueException('Entity type parameter must be included in context.');
    }
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
  
    // The bundle property behaves differently from other entity properties.
    // i.e. the nested structure with a 'value' key does not work.
    if ($entity_type->hasKey('bundle')) {
      $bundle_key = $entity_type->getKey('bundle');
      $type = $data[$bundle_key][0]['value'];
      $data[$bundle_key] = $type;
    }

    // Attributes specific to the Couch API overwrites Drupal specific fields.
    if (isset($data['_id'])) {
      // @todo Fetch the uuid key from the entity definition.
      $data['uuid'] = array(array('value' => $data['_id']));
    }
    if (isset($data['_rev'])) {
      $data['_revs_info'] = array(array('rev' => $data['_rev']));
    }

    // Clean-up attributes we don't needs anymore.
    foreach (array('_id', '_rev', '_entity_type') as $key) {
      if (isset($data[$key])) {
        unset($data[$key]);
      }
    }
  
    return $this->entityManager->getStorage($entity_type_id)->create($data);
  }
}
