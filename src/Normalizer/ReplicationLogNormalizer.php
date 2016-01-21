<?php

namespace Drupal\relaxed\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Drupal\relaxed\Entity\ReplicationLog;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ReplicationLogNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = array('Drupal\relaxed\Entity\ReplicationLogInterface');

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
  public function __construct(EntityManagerInterface $entity_manager, UuidIndexInterface $uuid_index, RevisionTreeIndexInterface $rev_tree, LinkManagerInterface $link_manager, SelectionPluginManagerInterface $selection_manager = NULL) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revTree = $rev_tree;
    $this->linkManager = $link_manager;
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    // Strictly format the entity how CouchDB expects it, plus out JSON-LD data.
    $data = [
      '@context' => [
        '_id' => '@id',
        'replication_log' => $this->linkManager->getTypeUri(
          'replication_log',
          $entity->bundle()
        ),
      ],
      '@type' => 'replication_log',
      '_id' => $entity->uuid(),
      '_rev' => $entity->_rev->value,
      'history' => [],
      'session_id' => $entity->getSessionId(),
      'source_last_seq' => $entity->getSourceLastSeq(),
    ];

    return $data;
  }
  /**
   * @inheritDoc
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // Simply create the replication log entity.
    try {
      $entity = ReplicationLog::create($data);
      return $entity;
    }
    catch(Exception $e) {
      watchdog_exception('Relaxed', $e);
    }
  }

  public function supportsDenormalization($data, $type, $format = NULL) {
    // We need to accept both ReplicationLog and ContentEntityInterface classes.
    // LocalDocResource entities are treated as standard documents (content entities)
    if (in_array($type, ['Drupal\Core\Entity\ContentEntityInterface', 'Drupal\relaxed\Entity\ReplicationLog'], true)) {
      // If a document doesn't have a type set, we assume it's a replication log.
      // We also support documents specifically specified as replication logs.
      if (!isset($data['@type']) || $data['@type'] === 'replication_log') {
        return true;
      }
    }
    return false;
  }
}
