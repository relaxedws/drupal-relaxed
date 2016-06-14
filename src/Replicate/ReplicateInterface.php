<?php

namespace Drupal\relaxed\Replicate;

interface ReplicateInterface {

  /**
   * Set the source database.
   *
   * @param array $info
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setSource($info);

  /**
   * Set the target database.
   *
   * @param array $info
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setTarget($info);

  /**
   * Run replication.
   *
   * @return mixed
   */
  public function doReplication();

  /**
   * Returns the result of the replication.
   *
   * @return mixed
   */
  public function getResult();

  /**
   * Set continuous replication.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setContinuous();

  /**
   * Set create the target before replication.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setCreateTarget();

  /**
   * Set cancel replication.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setCancel();

  /**
   * Set doc IDs for replication.
   *
   * @param array $doc_ids
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   */
  public function setDocIds(array $doc_ids);
  
}
