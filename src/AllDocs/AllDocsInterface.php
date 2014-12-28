<?php

namespace Drupal\relaxed\AllDocs;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface AllDocsInterface {

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public static function createInstance(ContainerInterface $container, EntityManagerInterface $entity_manager, MultiversionManagerInterface $multiversion_manager, WorkspaceInterface $workspace);

  /**
   * @param boolean $include_docs
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function includeDocs($include_docs);

  /**
   * @param int $limit
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function limit($limit);

  /**
   * @param int $skip
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function skip($skip);

  /**
   * @param boolean $descending
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function descending($descending);

  /**
   * @param string $key
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function startKey($key);

  /**
   * @param string $key
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function endKey($key);

  /**
   * @param boolean $inclusive_end
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public function inclusiveEnd($inclusive_end);

  /**
   * @return array
   */
  public function execute();

}
