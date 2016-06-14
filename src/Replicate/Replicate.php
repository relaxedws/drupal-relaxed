<?php

namespace Drupal\relaxed\Replicate;

use Doctrine\CouchDB\CouchDBClient;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;

class Replicate implements ReplicateInterface {

  /**
   * @var \Doctrine\CouchDB\CouchDBClient
   */
  protected $source;

  /**
   * @var \Doctrine\CouchDB\CouchDBClient
   */
  protected $target;

  /**
   * @var array
   */
  protected $result = [];

  /**
   * @var
   */
  protected $repId = NULL;

  /**
   * @var bool
   */
  protected $continuous = FALSE;

  /**
   * @var
   */
  protected $filter = NULL;

  /**
   * @var bool
   */
  protected $createTarget = FALSE;

  /**
   * @var array|NULL
   */
  protected $docIds = NULL;

  /**
   * @var int
   */
  protected $heartbeat = 10000;

  /**
   * @var int
   */
  protected $timeout = 10000;

  /**
   * @var bool
   */
  protected $cancel;

  /**
   * @var string
   */
  protected $style = 'all_docs';

  /**
   * @var
   */
  protected $sinceSeq = 0;

  /**
   * {@inheritdoc}
   */
  public function setSource($info) {
    $this->source = CouchDBClient::create($info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget($info) {
    $this->target = CouchDBClient::create($info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setContinuous() {
    $this->continuous = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreateTarget() {
    $this->createTarget = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancel() {
    $this->cancel = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDocIds(array $doc_ids) {
    $this->docIds = $doc_ids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function doReplication() {
    $task = new ReplicationTask(
      $this->repId,
      $this->continuous,
      $this->filter,
      $this->createTarget,
      $this->docIds,
      $this->heartbeat,
      $this->timeout,
      $this->cancel,
      $this->style,
      $this->sinceSeq
    );
    $replicator = new Replicator($this->source, $this->target, $task);
    $this->result = $replicator->startReplication();
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return $this->result;
  }

}
