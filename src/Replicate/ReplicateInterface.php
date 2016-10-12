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

  /**
   * Set the ID of the filter plugin to use during replication.
   *
   * @param string $filter
   *   The plugin ID of a ReplicationFilterInterface.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   *   Returns $this.
   */
  public function setFilter($filter = NULL);

  /**
   * Set the parameters for the filter plugin.
   *
   * @param array|NULL $parameters
   *   An associative array of name-value parameters.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   *   Returns $this.
   */
  public function setParameters(array $parameters = NULL);

  /**
   * Set a parameter for the filter plugin.
   *
   * If no parameter bag yet exists, an empty parameter bag will be created.
   *
   * @param string $name
   *   The parameter name to set.
   * @param string $value
   *   The value for the parameter.
   *
   * @return \Drupal\relaxed\Replicate\ReplicateInterface
   *   Returns $this.
   */
  public function setParameter($name, $value);
  
}
