<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\Normalizer\NormalizerTestBase.
 */

namespace Drupal\Tests\relaxed\Unit\Normalizer;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;

abstract class NormalizerTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'serialization',
    'system',
    'field',
    'entity_test',
    'text',
    'filter',
    'user'
  ];

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['url_alias', 'router']);
    $this->installConfig(['field']);
    \Drupal::service('router.builder')->rebuild();
    \Drupal::moduleHandler()->invoke('rest', 'install');

    // Auto-create a field for testing.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ])->save();

    $this->installSchema('key_value', ['key_value_sorted']);
    \Drupal::service('multiversion.manager')->enableEntityTypes();

    $this->serializer = $this->container->get('serializer');

    $workspace = Workspace::create(['machine_name' => 'default']);
    $workspace->save();
  }

}
