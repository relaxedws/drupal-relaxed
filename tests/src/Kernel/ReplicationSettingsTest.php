<?php

namespace Drupal\Tests\relaxed\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test operations on ReplicationSettings config entity.
 *
 * @group relaxed
 */
class ReplicationSettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'serialization',
    'key_value',
    'workspaces',
    'multiversion',
    'relaxed',
  ];

  /**
   * Test creation of ReplicationSettings config entity.
   */
  public function testCreation() {
    $this->installEntitySchema('replication_settings');
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entity = $entityTypeManager->getStorage('replication_settings')->create([
      'id' => 'test',
      'label' => 'Replication settings test',
      'filter_id' => 'entity_type',
      'parameters' => ['entity_type_id' => 'node', 'bundle' => 'article'],
    ]);
    $entity->save();

    $entity = $entityTypeManager->getStorage('replication_settings')->load('test');

    $this->assertEquals($entity->id(), 'test', 'Test replication settings config entity successfully saved.');
  }

}
