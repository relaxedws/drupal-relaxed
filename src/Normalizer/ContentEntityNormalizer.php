<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\UuidIndex;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @todo Don't extend EntityNormalizer. Follow the pattern of
 *   \Drupal\hal\Entity\Normalizer\ContentEntityNormalizer
 */
class ContentEntityNormalizer extends NormalizerBase implements DenormalizerInterface {

  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\ContentEntityInterface');

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\UuidIndex $uuid_index
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
    // New or mocked entities might not have a rev yet.
    if (!empty($entity->_revs_info->rev)) {
      $data['_rev'] = $entity->_revs_info->rev;
    }
    $data['_entity_type'] = $context['entity_type'] = $entity->getEntityTypeId();

    foreach ($entity as $name => $field) {
      $field_data = $this->serializer->normalize($field, $format, $context);
      if ($field_data !== NULL) {
        $data[$name] = $field_data;
      }
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
      if (isset($data[$bundle_key][0]['value'])) {
        $type = $data[$bundle_key][0]['value'];
        $data[$bundle_key] = $type;
      }
    }

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

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityManager->getStorage($entity_type_id)->create($data);

    // Check if the entity already exists to know what state we should set.
    if ($item = $this->uuidIndex->get($entity->uuid())) {
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(FALSE);
    }
    return $entity;
  }
}
