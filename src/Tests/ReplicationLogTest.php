<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\ReplicationLogTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\relaxed\Entity\ReplicationLog;
use Drupal\multiversion\Tests\MultiversionWebTestBase;

/**
 * Test the creation and operation of the Replication Log entity.
 *
 * @group relaxed
 */
class ReplicationLogTest extends MultiversionWebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['entity_test', 'relaxed'];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  public function testOperations() {
    $entity = $this->entityTypeManager->getStorage('replication_log')->create();
    $this->assertTrue($entity instanceof ReplicationLog, 'Replication Log entity was created.');

    // Set required fields.
    $entity = $this->entityTypeManager->getStorage('replication_log')->create();
    $seq_id = \Drupal::service('multiversion.manager')->newSequenceId();
    $entity->source_last_seq->value = $seq_id;
    $entity->history->recorded_seq = $seq_id;

    try {
      $entity->save();
      $this->fail('Required history column was enforced.');
    }
    catch(EntityStorageException $e) {
      $this->pass('Required history column was enforced.');
    }

    // Try again with the remaining required field set.
    $entity->history->session_id = \Drupal::service('uuid')->generate();
    $entity->save();

    $entity_id = $entity->id();
    $this->assertTrue(!empty($entity_id), 'Entity was saved.');

    $max_int = 2147483647;
    $max_bigint = 9223372036854775807;
    $entity = $this->entityTypeManager->getStorage('replication_log')->create();
    $entity->history->start_last_seq = $max_int;
    $entity->history->missing_found = $max_int;
    $entity->history->docs_read = $max_int;
    $entity->history->end_last_seq = $max_int;
    $entity->history->missing_checked = $max_int;
    $entity->history->docs_written = $max_int;
    $entity->history->doc_write_failures = $max_int;
    $entity->history->recorded_seq = $max_bigint;
    $entity->history->start_last_seq = $max_int;
    $entity->history->end_time = date(DATE_RFC2822);
    $entity->history->start_time = date(DATE_RFC2822);
    $entity->history->session_id = \Drupal::service('uuid')->generate();

    try {
      $entity->save();
      $this->pass('Entity was saved.');
    }
    catch(EntityStorageException $e) {
      $this->fail('Fail, trying to save entity with incorrect data format or length for history fields.');
    }
  }

}
