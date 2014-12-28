<?php

/**
 * @file
 * Contains \Drupal\relaxed\RevisionDiff\RevisionDiffInterface.
 */

namespace Drupal\relaxed\RevisionDiff;

use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface RevisionDiffInterface {

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @return \Drupal\relaxed\RevisionDiff\RevisionDiffInterface
   */
  static public function createInstance(ContainerInterface $container, RevisionIndexInterface $rev_index, WorkspaceInterface $workspace);

  /**
   * @param array $revision_ids
   */
  public function setRevisionIds(array $revision_ids);

  /**
   * @return array
   */
  public function getRevisionIds();

  /**
   * Returns missing revisions ids.
   * @return array
   */
  public function getMissing();

}
