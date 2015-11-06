<?php

namespace Drupal\relaxed\AllDocs;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\Index\EntityIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class AllDocs implements AllDocsInterface {
  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * @var \Drupal\multiversion\Entity\Index\EntityIndexInterface
   */
  protected $entityIndex;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var boolean
   */
  protected $includeDocs = FALSE;

  /**
   * @var int
   */
  protected $limit = NULL;

  /**
   * @var int
   */
  protected $skip = 0;

  /**
   * @var boolean
   */
  protected $descending = FALSE;

  /**
   * @var string
   */
  protected $startKey;

  /**
   * @var string
   */
  protected $endKey;

  /**
   * @var boolean
   */
  protected $inclusiveEnd = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, WorkspaceInterface $workspace) {
    return new static(
      $container->get('entity.manager'),
      $container->get('multiversion.manager'),
      $workspace,
      $container->get('entity.index.id'),
      $container->get('serializer')
    );
  }

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\multiversion\Entity\Index\EntityIndexInterface $entity_index
   */
  public function __construct(EntityManagerInterface $entity_manager, MultiversionManagerInterface $multiversion_manager, WorkspaceInterface $workspace, EntityIndexInterface $entity_index, SerializerInterface $serializer) {
    $this->entityManager = $entity_manager;
    $this->multiversionManager = $multiversion_manager;
    $this->workspace = $workspace;
    $this->entityIndex = $entity_index;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function includeDocs($include_docs) {
    $this->includeDocs = $include_docs;
  }

  /**
   * {@inheritdoc}
   */
  public function limit($limit) {
    $this->limit = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function skip($skip) {
    $this->skip = $skip;
  }

  /**
   * {@inheritdoc}
   */
  public function descending($descending) {
    $this->descending = $descending;
  }

  /**
   * {@inheritdoc}
   */
  public function startKey($key) {
    $this->startKey = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function endKey($key) {
    $this->endKey = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function inclusiveEnd($inclusive_end) {
    $this->inclusiveEnd = $inclusive_end;
  }

  /**
   * {@inheritdoc}
   *
   * @todo {@link https://www.drupal.org/node/2599900 Move any logic around 'includeDocs' and the serialization format
   *   into the serializer to better separate concerns.}
   */
  public function execute() {
    $rows = array();

    $entity_types = $this->entityManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($this->multiversionManager->isEnabledEntityType($entity_type) && !$entity_type->get('local')) {
        try {
          $ids = $this->entityManager
            ->getStorage($entity_type_id)
            ->getQuery()
            ->condition('workspace', $this->workspace->id())
            ->execute();
        }
        catch (\Exception $e) {
          watchdog_exception('relaxed', $e);
          continue;
        }

        $keys = array();
        foreach ($ids as $id) {
          $keys[] = $entity_type_id . ':' . $id;
        }
        $items = $this->entityIndex->getMultiple($keys);
        foreach ($items as $item) {
          $rows[$item['uuid']] = array('rev' => $item['rev']);
        }

        if ($this->includeDocs) {
          $entities = $this->entityManager->getStorage($entity_type_id)->loadMultiple($ids);
          foreach ($entities as $entity) {
            if (strpos($entity->_rev->value, '1-101010101010101010101010') !== FALSE) {
              continue;
            }
            $rows[$entity->uuid()]['doc'] = $this->serializer->normalize($entity, 'json');
          }
        }
      }
    }

    return $rows;
  }

}
