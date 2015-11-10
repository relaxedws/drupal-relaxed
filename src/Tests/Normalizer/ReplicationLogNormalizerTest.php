<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\Normalizer\ReplicationLogNormalizerTest.
 */

namespace Drupal\relaxed\Tests\Normalizer;
use Drupal\relaxed\Entity\ReplicationLog;

/**
 * Tests the replication_log serialization format.
 *
 * @group relaxed
 */
class ReplicationLogNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'relaxed');

  protected $entityClass = 'Drupal\relaxed\Entity\ReplicationLog';

  protected function setUp() {
    parent::setUp();

    $this->entity = ReplicationLog::create(['source_last_seq' => 99]);
  }

  public function testNormalize() {
    $expected = [
      '@context' => array(
        '_id' => '@id',
        'replication_log' => \Drupal::service('rest.link_manager')->getTypeUri(
          'replication_log',
          $this->entity->bundle()
        ),
      ),
      '@type' => 'replication_log',
      '_id' => $this->entity->uuid(),
      '_rev' => '0-00000000000000000000000000000000',
      'history' => [],
      'session_id' => $this->entity->getSessionId(),
      'source_last_seq' => $this->entity->getSourceLastSeq(),
    ];

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $field_name) {
      $this->assertEqual($expected[$field_name], $normalized[$field_name], "Field $field_name is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');

    $entity = ReplicationLog::create();
    $normalized = $this->serializer->normalize($entity);
    $this->assertIdentical(NULL, $normalized['source_last_seq'], "Field is normalized correctly when emtpy.");
  }

  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, 'Denormalized entity is an instance of ' . $this->entityClass);
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');

    $this->assertTrue(!empty($denormalized->session_id->value), 'session_id denormalized correctly.');
    $this->assertTrue(!empty($denormalized->source_last_seq->value), 'source_last_seq denormalized correctly.');
  }

}
