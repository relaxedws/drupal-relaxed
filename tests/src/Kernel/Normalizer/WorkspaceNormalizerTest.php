<?php

namespace Drupal\Tests\relaxed\Kernel\Normalizer;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the workspace serialization format.
 *
 * @group relaxed
 */
class WorkspaceNormalizerTest extends NormalizerTestBase {

  protected $entityClass = 'Drupal\workspaces\Entity\Workspace';

  protected function setUp() {
    parent::setUp();
    $name = $this->randomMachineName();
    $this->entity = $this->createWorkspace($name);
    $this->entity->save();
  }

  public function testNormalizer() {
    // Test normalize.
    $expected = [
      'db_name' => (string) $this->entity->id(),
      'instance_start_time' => (string) $this->entity->getStartTime(),
      'update_seq' => 0,
    ];
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
    $this->assertTrue($denormalized instanceof $this->entityClass, new FormattableMarkup('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
  }

  /**
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($id) {
    return Workspace::create(['id' => $id]);
  }

}
