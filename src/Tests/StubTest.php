<?php

namespace Drupal\relaxed\Tests;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\multiversion\Entity\Workspace;
use Drupal\simpletest\WebTestBase;

/**
 * @group multiversion
 *
 * @todo It's too late at night and I can't figure out how to get this test to
 *   work with KernelTestBase. Fix this when you've had some sleep.
 */
class StubTest extends WebTestBase {

  use EntityReferenceTestTrait;

  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'entity_test',
    'relaxed'
  ];

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();

    $entity_type_id = 'entity_test_mulrev';
    $this->createEntityReferenceField($entity_type_id, $entity_type_id, 'field_ref', 'Reference', $entity_type_id);
  }

  public function testStubCreation() {
    $entity_type_id = 'entity_test_mulrev';

    /** @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = \Drupal::service('serializer');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $normalized = [
      '@context' => [
        '@language' => 'en',
      ],
      '@type' => $entity_type_id,
      '_id' => 'fe36b529-e2d7-4625-9b07-7ee8f84928b2',
      'en' => [
        '@context' => [
          '@language' => 'en',
        ],
        'name' => [],
        'type' => [['value' => $entity_type_id]],
        'created' => [['value' => 1447877434]],
        'user_id' => [['target_id' => 1]],
        'default_langcode' => [['value' => TRUE]],
        'field_ref' => [[
          'entity_type_id' => $entity_type_id,
          'target_uuid' => '0aec21a0-8e36-11e5-8994-feff819cdc9f'
        ]],
      ],
    ];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $serializer->deserialize(json_encode($normalized), '\Drupal\entity_test\Entity\EntityTestMulRev', 'json');

    $violations = $entity->validate();
    $this->assertEqual(0, count($violations), 'There are no validation violations.');

    $entity->save();

    // Ensure that the references entity way created.
    $references = $entity_type_manager
      ->getStorage('entity_test_mulrev')
      ->loadByProperties(['uuid' => '0aec21a0-8e36-11e5-8994-feff819cdc9f']);
    $reference = reset($references);

    $this->assertTrue(!empty($reference), 'The referenced entity was saved when serialized.');
    $this->assertTrue($reference->_rev->is_stub, 'The references entity was saved as a stub.');

    // Ensure that we now have the correct number of entities in the system.
    $entities = $entity_type_manager
      ->getStorage('entity_test_mulrev')
      ->loadMultiple();

    $this->assertEqual(2, count($entities), 'There total of entities is correct.');
  }

}
