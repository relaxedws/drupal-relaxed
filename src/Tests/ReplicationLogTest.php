<?php

namespace Drupal\relaxed\Tests;

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

  public function testFields() {
    $entity = entity_create('replication_log');

    $this->assertTrue($entity instanceof ReplicationLog, 'Replication Log entity was created.');
  }

  public function testOperations() {
    $entity = entity_create('replication_log');

    $entity->save();
    $this->assertTrue(!empty($entity->id()), 'Entity was saved.');
  }
}
