<?php

namespace Drupal\relaxed\AllDocs;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface AllDocsInterface {

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return \Drupal\relaxed\AllDocs\AllDocsInterface
   */
  public static function createInstance(ContainerInterface $container, WorkspaceInterface $workspace);

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
