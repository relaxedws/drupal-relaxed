<?php

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Component\Utility\String;
use Drupal\serialization\Tests\NormalizerTestBase;

/**
 * Tests the content serialization format.
 *
 * @group relaxed
 */
class BulkDocsNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'uuid', 'relaxed');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Array with test entities.
   */
  protected $testEntities = array();

  /**
   * Array with test values for test entities.
   */
  protected $testValues = array();

  /**
   * Number of test values to generate.
   */
  protected $testValuesNumber = 3;

  protected function setUp() {
    parent::setUp();
    $this->installSchema('key_value', array('key_value_sorted'));

    \Drupal::service('multiversion.manager')
      ->attachRequiredFields('entity_test_mulrev', 'entity_test_mulrev');

    $this->testEntities = $this->createTestEntities('entity_test_mulrev', $this->testValuesNumber);
    $this->serializer = $this->container->get('serializer');
  }

  public function testNormalize() {
    $expected = array();
    for ($key = 0; $key < $this->testValuesNumber; $key++) {
      $entity = entity_load('entity_test_mulrev', $key+1);

      $expected[] = array(
        'id' => array(
          array('value' => $key+1),
        ),
        'revision_id' => array(
          array('value' => $key+1),
        ),
        'uuid' => array(
          array('value' => $this->testEntities[$key]->uuid()),
        ),
        'langcode' => array(
          array('value' => 'en'),
        ),
        'name' => array(
          array('value' => $this->testValues[$key]['name']),
        ),
        'type' => array(
          array('value' => 'entity_test_mulrev'),
        ),
        'user_id' => array(
          array('target_id' => $this->testValues[$key]['user_id']),
        ),
        'field_test_text' => array(
          array(
            'value' => $this->testValues[$key]['field_test_text']['value'],
            'format' => $this->testValues[$key]['field_test_text']['format'],
          ),
        ),
        '_id' => $entity->uuid(),
        '_rev' => $entity->_revs_info->rev,
        '_deleted' => $entity->_deleted->value,
        '_local_seq' => $entity->_local_seq->id,
        '_entity_type' => $entity->getEntityTypeId(),
      );
    }

    $normalized = $this->serializer->normalize($this->testEntities);

    $entity_number = 1;
    foreach ($expected as $key => $expected_entity) {
      foreach (array_keys($expected_entity) as $entity_key) {
        $this->assertEqual($expected_entity[$entity_key], $normalized[$key][$entity_key], "Field $entity_key is normalized correctly for entity number $entity_number.");
      }
      $this->assertEqual(array_diff_key($normalized[$key], $expected[$key]), array(), 'No unexpected data is added to the normalized array.');
      $entity_number++;
    }
  }

  public function testSerialize() {
    $normalized = $this->serializer->normalize($this->testEntities);
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->testEntities, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes correctly to JSON.');
  }

  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->testEntities);
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    if (is_array($denormalized)) {
      foreach ($denormalized as $key => $entity) {
        $entity_number = $key+1;
        $this->assertTrue($entity instanceof $this->entityClass, String::format("Denormalized entity number $entity_number is an instance of @class", array('@class' => $this->entityClass)));
        $this->assertIdentical($entity->getEntityTypeId(), $this->testEntities[$key]->getEntityTypeId(), "Expected entity type foundfor entity number $entity_number.");
        $this->assertIdentical($entity->bundle(), $this->testEntities[$key]->bundle(), "Expected entity bundle found for entity number $entity_number.");
        $this->assertIdentical($entity->uuid(), $this->testEntities[$key]->uuid(), "Expected entity UUID found for entity number $entity_number.");
      }
    }

    // @todo Test context switches.
  }

  protected function createTestEntities($entity_type, $number = 3) {
    $entities = array();

    while ($number >= 1) {
      $values = array(
        'name' => $this->randomMachineName(),
        'user_id' => 0,
        'field_test_text' => array(
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        )
      );
      $this->testValues[] = $values;
      $entity = entity_create($entity_type, $values);
      $entity->save();
      $entities[] = $entity;
      $number--;
    }

    return $entities;
  }

}
