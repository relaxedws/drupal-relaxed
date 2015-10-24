<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\Normalizer\ContentEntityNormalizerTest.
 */

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the content serialization format.
 *
 * @group relaxed
 */
class ContentEntityNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'relaxed');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    // @todo: {@link https://www.drupal.org/node/2600468 Attach a file field.}

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
    $entity = entity_load('entity_test_mulrev', 1);
    $entity_rev = $entity->_rev->value;

    $expected = array(
      '@context' => array(
        '_id' => '@id',
        'entity_test_mulrev' => \Drupal::service('rest.link_manager')->getTypeUri(
          'entity_test_mulrev',
          $entity->bundle()
        ),
      ),
      '@type' => 'entity_test_mulrev',
      'langcode' => array(
        array('value' => 'en'),
      ),
      'name' => array(
        array('value' => $this->values['name']),
      ),
      'type' => array(
        array('value' => 'entity_test_mulrev'),
      ),
      'created' => array(
        array('value' => $this->entity->created->value),
      ),
      'default_langcode' => array(
        array('value' => TRUE),
      ),
      'user_id' => array(
        array('target_id' => $this->values['user_id']),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->values['field_test_text']['value'],
          'format' => $this->values['field_test_text']['format'],
        ),
      ),
      '_id' => $entity->uuid(),
      '_rev' => $entity_rev ?: NULL,
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');

    // Test normalization when is set the revs query parameter.
    $parts = explode('-', $entity->_rev->value);
    $expected['_revisions'] = array(
      'ids' => array($parts[1]),
      'start' => (int) $parts[0],
    );

    $normalized = $this->serializer->normalize($this->entity, NULL, array('query' => array('revs' => TRUE)));

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertTrue($expected['_revisions']['start'] === $normalized['_revisions']['start'], "Correct data type for the start field.");
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');

    // @todo {@link https://www.drupal.org/node/2600460 Test context switches.}
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
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertIdentical($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertIdentical($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');

    // @todo {@link https://www.drupal.org/node/2600460 Test context switches.}
  }

}
