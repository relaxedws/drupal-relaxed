<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsNormalizerTest.
 */

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\relaxed\AllDocs\AllDocs;

/**
 * Tests the serialization format for AllDocsNormalizer.
 *
 * @group relaxed
 */
class AllDocsNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'uuid', 'relaxed');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities = array();

  protected function setUp() {
    parent::setUp();

    $this->entities = array();
    for ($i = 0; $i < 3; $i++) {
      $this->entities[$i] = entity_create('entity_test_mulrev');
      $this->entities[$i]->save();
    }
  }

  public function testNormalize() {
    $expected = array(
      'total_rows' => 3,
      'offset' => 0,
      'rows' => array()
    );

    foreach ($this->entities as $entity) {
      $expected['rows'][] = array(
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => array(
          'rev' => $entity->_revs_info->rev,
        ),
      );
    }

    /** @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspace.manager');

    $all_docs = AllDocs::createInstance(
      $this->container,
      \Drupal::service('entity.manager'),
      \Drupal::service('multiversion.manager'),
      $workspace_manager->getActiveWorkspace()
    );

    $normalized = \Drupal::service('relaxed.normalizer.all_docs')->normalize($all_docs);

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Correct value for $key key.");
    }
  }
}
