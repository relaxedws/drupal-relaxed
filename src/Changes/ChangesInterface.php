<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\ChangesInterface.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\Index\SequenceIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface ChangesInterface {

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\multiversion\Entity\Index\SequenceIndexInterface $sequence_index
   *   The sequence index.
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *  The workspace instance
   *
   * @return static
   */
  static public function createInstance(ContainerInterface $container, SequenceIndexInterface $sequence_index, WorkspaceInterface $workspace);

  /**
   * Sets the include_docs flag.
   *
   * @param boolean $include_docs
   *  The include docs flag to set.
   *
   * @return $this
   */
  public function setIncludeDocs($include_docs);

  /**
   * Sets from what sequence number to check for changes.
   *
   * @param int $seq
   *   The sequence to set.
   *
   * @return $this
   */
  public function setLastSeq($seq);

  /**
   * Get the changes in a 'normal' way.
   */
  public function getNormal();

  /**
   * Get the changes with a 'longpoll'.
   *
   * We can implement this method later.
   *
   * @see https://www.drupal.org/node/2282295
   */
  public function getLongpoll();

}
