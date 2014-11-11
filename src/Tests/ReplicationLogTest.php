<?php

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

  // @todo 'node' needs to be enabled because relaxed depends on rest that seems
  // to assumes certain things incorrectly. Revisit this.
  public static $modules = array('entity_test', 'node', 'relaxed');

  public function testOperations() {
    $entity = entity_create('replication_log');

    $this->assertTrue($entity instanceof ReplicationLog, 'Replication Log entity was created.');

    // Set required fields.
    $entity = entity_create('replication_log');
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
  }
}
