<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\Normalizer\AllDocsNormalizerTest.
 */

namespace Drupal\Tests\relaxed\Unit\Normalizer;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\relaxed\AllDocs\AllDocs;

/**
 * Tests the serialization format for AllDocsNormalizer.
 *
 * @group relaxed
 */
class AllDocsNormalizerTest extends NormalizerTestBase {

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

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities = [];

  protected function setUp() {
    parent::setUp();

    $values = [
      'name' => $this->randomMachineName(),
      'user_id' => 0,
      'field_test_text' => [
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ],
    ];

    $this->entities = [];
    for ($i = 0; $i < 3; $i++) {
      $this->entities[$i] = EntityTestMulRev::create($values);
      $this->entities[$i]->save();
    }
  }

  public function testNormalize() {
    /** @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspace.manager');
    $serializer = \Drupal::service('serializer');
    $normalizer = \Drupal::service('relaxed.normalizer.all_docs');

    $all_docs = AllDocs::createInstance(
      $this->container,
      $workspace_manager->getActiveWorkspace()
    );

    // Test without including docs.
    $expected = [
      'total_rows' => 3,
      'offset' => 0,
      'rows' => [],
    ];
    foreach ($this->entities as $entity) {
      $expected['rows'][] = [
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => [
          'rev' => $entity->_rev->value,
        ],
      ];
    }

    $normalized = $normalizer->normalize($all_docs);
    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Correct value for $key key when not including docs.");
    }

    // Test with including docs.
    $expected = [
      'total_rows' => 3,
      'offset' => 0,
      'rows' =>[],
    ];
    foreach ($this->entities as $entity) {
      $expected['rows'][] = [
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => [
          'rev' => $entity->_rev->value,
          'doc' => $serializer->normalize($entity, 'json'),
        ],
      ];
    }

    $all_docs->includeDocs(TRUE);
    $normalized = $normalizer->normalize($all_docs);
    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Correct value for $key key when including docs.");
    }
  }

}
