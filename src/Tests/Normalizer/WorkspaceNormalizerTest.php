<?php

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Core\Language\Language;
use Drupal\Component\Utility\String;
use Drupal\serialization\Tests\NormalizerTestBase;

/**
 * Tests the workspace serialization format.
 *
 * @group relaxed
 */
class WorkspaceNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'uuid', 'rest', 'relaxed');

  protected $entityClass = 'Drupal\multiversion\Entity\Workspace';

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();
    $this->installSchema('key_value', array('key_value_sorted'));
    $this->installSchema('multiversion', array('workspace'));

    \Drupal::service('multiversion.manager')
      ->attachRequiredFields('entity_test_mulrev', 'entity_test_mulrev');

    $this->entity = entity_create('workspace', array('name' => $this->randomName()));
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');
  }

  public function testNormalize() {
    $expected = array(
      'id' => 1,
      'db_name' => $this->entity->name(),
    );
    $normalized = $this->serializer->normalize($this->entity);
    foreach (array_keys($expected) as $fieldName) {
      $this->assertEqual($expected[$fieldName], $normalized[$fieldName], "Field $fieldName is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

  public function testSerialize() {
    $normalized = $this->serializer->normalize($this->entity);
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes correctly to JSON.');
  }

  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, String::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
  }
}
