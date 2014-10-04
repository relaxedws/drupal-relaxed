<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\ChangesInterface.
 */

namespace Drupal\relaxed\Changes;

interface ChangesInterface {

  /**
   * Sets from what workspace to fetch changes.
   */
  public function useWorkspace($name);

  /**
   * Sets from what sequence number to check for changes.
   */
  public function lastSeq($seq);

  /**
   * Return the changes in a 'normal' way.
   */
  public function getNormal();

  /**
   * Return the changes with a 'longpoll'.
   *
   * We can implement this method later.
   *
   * @see https://www.drupal.org/node/2282295
   */
  public function getLongpoll();

}
