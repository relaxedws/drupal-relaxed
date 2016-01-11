<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\Normalizer\WorkspaceNormalizerTest.
 */

namespace Drupal\Tests\relaxed\Unit\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\multiversion\Entity\Workspace;

/**
 * Tests the workspace serialization format.
 *
 * @group relaxed
 */
class WorkspaceNormalizerTest extends NormalizerTestBase {

  public static $modules = [
    'serialization',
    'system',
    'field',
    'entity_test',
    'text',
    'filter',
    'user',
    'key_value',
    'multiversion',
    'rest',
    'relaxed'
  ];

  protected $entityClass = 'Drupal\multiversion\Entity\Workspace';

  protected function setUp() {
    parent::setUp();
    $name = $this->randomMachineName();
    $this->entity = $this->createWorkspace($name);
    $this->entity->save();
  }

  public function testNormalizer() {
    // Test normalize.
    $expected = array(
      'db_name' => (string) $this->entity->getMachineName(),
      'instance_start_time' => (string) $this->entity->getStartTime(),
    );
    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $fieldName) {
      $this->assertEquals($expected[$fieldName], $normalized[$fieldName], "Field $fieldName is normalized correctly.");
    }
    $this->assertTrue(is_string($normalized['instance_start_time']), 'Instance start time is a string.');
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // Test serialize.
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertSame($expected, $actual, 'Entity serializes correctly to JSON.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
  }

  /**
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($name) {
    return Workspace::create(['machine_name' => $name]);
  }

}
