<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\Normalizer\BulkDocsNormalizerTest.
 */

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\relaxed\BulkDocs\BulkDocs;
use Drupal\relaxed\BulkDocs\BulkDocsInterface;

/**
 * Tests the content serialization format.
 *
 * @group relaxed
 */
class BulkDocsNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'relaxed');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * Array with test entities.
   */
  protected $testEntities = array();

  /**
   * @var \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  protected $bulkDocs;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

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
    $this->testEntities = $this->createTestEntities('entity_test_mulrev', $this->testValuesNumber);

    $this->workspaceManager = $this->container->get('workspace.manager');

    $this->bulkDocs = BulkDocs::createInstance(
      $this->container,
      $this->workspaceManager,
      $this->workspaceManager->getActiveWorkspace()
    );

    $this->bulkDocs->setEntities($this->testEntities);
    $this->bulkDocs->save();
  }

  public function testNormalize() {
    $expected = array();
    for ($key = 0; $key < $this->testValuesNumber; $key++) {
      $entity = entity_load('entity_test_mulrev', $key+1);
      $expected[$key] = array(
        'ok' => TRUE,
        'id' => $entity->uuid(),
        'rev' => $entity->_rev->value,
      );
    }

    $normalized = $this->serializer->normalize($this->bulkDocs);

    $entity_number = 1;
    foreach ($expected as $key => $value) {
      foreach (array_keys($value) as $value_key) {
        $this->assertEqual($value[$value_key], $normalized[$key][$value_key], "Field $value_key is normalized correctly for entity number $entity_number.");
      }
      $this->assertEqual(array_diff_key($normalized[$key], $expected[$key]), array(), 'No unexpected data is added to the normalized array.');
      $entity_number++;
    }
  }

  public function testSerialize() {
    $normalized = $this->serializer->normalize($this->bulkDocs);
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->bulkDocs, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes correctly to JSON.');
  }

  public function testDenormalize() {
    $data = array('docs' => array());
    foreach ($this->testEntities as $entity) {
      $data['docs'][] = $this->serializer->normalize($entity);
    }
    $context = array('workspace' => $this->workspaceManager->getActiveWorkspace());
    $bulk_docs = $this->serializer->denormalize($data, 'Drupal\relaxed\BulkDocs\BulkDocs', 'json', $context);
    $this->assertTrue($bulk_docs instanceof BulkDocsInterface, 'Denormalized data is an instance of the correct interface.');
    foreach ($bulk_docs->getEntities() as $key => $entity) {
      $entity_number = $key+1;
      $this->assertTrue($entity instanceof $this->entityClass, SafeMarkup::format("Denormalized entity number $entity_number is an instance of @class", array('@class' => $this->entityClass)));
      $this->assertIdentical($entity->getEntityTypeId(), $this->testEntities[$key]->getEntityTypeId(), "Expected entity type foundfor entity number $entity_number.");
      $this->assertIdentical($entity->bundle(), $this->testEntities[$key]->bundle(), "Expected entity bundle found for entity number $entity_number.");
      $this->assertIdentical($entity->uuid(), $this->testEntities[$key]->uuid(), "Expected entity UUID found for entity number $entity_number.");
    }

    // @todo {@link https://www.drupal.org/node/2600460 Test context switches.}
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
