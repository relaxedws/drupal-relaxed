<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsNormalizerTest.
 */

namespace Drupal\relaxed\Tests\Normalizer;

/**
 * Tests the serialization format for AllDocsNormalizer.
 *
 * @group relaxed
 */
class AllDocsNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'uuid', 'relaxed');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => 0,
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
    );

    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();
  }

  public function testNormalize() {
    $uuid = $this->entity->uuid();
    $entity_type_id = $this->entity->getEntityTypeId();
    $expected = array(
      'id' => "$entity_type_id.$uuid",
      'key' => "$entity_type_id.$uuid",
      'value' => array(
        'rev' => $this->entity->_revs_info->rev,
      ),
    );

    $normalized = \Drupal::service('relaxed.normalizer.all_docs')->normalize($this->entity);

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Correct value for $key key.");
    }
    $this->assertEqual($expected['value']['rev'], $normalized['value']['rev'], "Correct revision.");
  }
}
