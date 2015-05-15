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
   *   The service container.
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *  The workspace instance
   *
   * @return static
   */
  public static function createInstance(ContainerInterface $container, WorkspaceInterface $workspace);

  /**
   * Sets the include_docs flag.
   * @todo: Rename to setIncludeDocs
   *
   * @param boolean $include_docs
   *  The include docs flag to set.
   *
   * @return $this
   */
  public function includeDocs($include_docs);

  /**
   * Sets the limit.
   * @todo: Rename to setLimit
   *
   * @param int $limit
   *   The limit to set.
   *
   * @return $this
   */
  public function limit($limit);

  /**
   * Sets the skip value.
   * @todo: Rename to setSkip
   *
   * @param int $skip
   *   The skip value to set.
   *
   * @return $this
   */
  public function skip($skip);

  /**
   * Set decending value.
   * @todo: Rename to setDecending
   *
   * @param boolean $descending
   *   The decending value to set.
   *
   * @return $this
   */
  public function descending($descending);

  /**
   * Sets the start key.
   * @todo: Rename to setStartKey
   *
   * @param string $key
   *   The start key to set.
   *
   * @return $this
   */
  public function startKey($key);

  /**
   * Sets the end key.
   * @todo: Rename to setEndKey
   *
   * @param string $key
   *   The end key to set.
   *
   * @return $this
   */
  public function endKey($key);

  /**
   * Sets the inclusive_end flag
   * @todo: Rename to setInclusiveEnd
   *
   * @param boolean $inclusive_end
   *   The inclusive end flag to set
   *
   * @return $this
   */
  public function inclusiveEnd($inclusive_end);

  /**
   * @todo: Document what this is about.
   *
   * @return array
   */
  public function execute();

}
