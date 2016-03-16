<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ReplicationLogNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\replication\Entity\ReplicationLog');

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndexInterface
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * @var string[]
   */
  protected $format = array('json');

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndexInterface $uuid_index
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UuidIndexInterface $uuid_index, LinkManagerInterface $link_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuidIndex = $uuid_index;
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    // Strictly format the entity how CouchDB expects it, plus our JSON-LD data.
    $data = [
      '@context' => [
        '_id' => '@id',
        'replication_log' => $this->linkManager->getTypeUri(
          'replication_log',
          $entity->bundle()
        ),
      ],
      '@type' => 'replication_log',
      '_id' => '_local/'. $entity->uuid(),
      '_rev' => $entity->_rev->value,
      'history' => $this->serializer->normalize($entity->get('history'), $format, $context),
      'session_id' => $entity->getSessionId(),
      'source_last_seq' => $entity->getSourceLastSeq(),
    ];

    return $data;
  }
  /**
   * @inheritDoc
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $data['_id'] = str_replace('_local/', '', $data['_id']);
    $record = $this->uuidIndex->get($data['_id']);
    if (!empty($record['entity_type_id']) && !empty($record['entity_id'])) {
      $storage = $this->entityTypeManager->getStorage($record['entity_type_id']);
      $entity = $storage->load($record['entity_id']);
      if ($entity instanceof ReplicationLogInterface) {
        foreach ($data as $name => $value) {
          $entity->{$name} = $value;
        }
        return $entity;
      }
    }

    try {
      $data['uuid'][0]['value'] = $data['_id'];
      $entity = ReplicationLog::create($data);
      return $entity;
    }
    catch(\Exception $e) {
      watchdog_exception('Relaxed', $e);
    }
  }

  public function supportsDenormalization($data, $type, $format = NULL) {
    // We need to accept both ReplicationLog and ContentEntityInterface classes.
    // LocalDocResource entities are treated as standard documents (content entities)
    if (in_array($type, ['Drupal\Core\Entity\ContentEntityInterface', 'Drupal\replication\Entity\ReplicationLog'], true)) {
      // If a document doesn't have a type set, we assume it's a replication log.
      // We also support documents specifically specified as replication logs.
      if (!isset($data['@type']) || $data['@type'] === 'replication_log') {
        return true;
      }
    }
    return false;
  }
}
