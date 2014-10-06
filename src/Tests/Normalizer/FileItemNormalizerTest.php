<?php

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\serialization\Tests\NormalizerTestBase;

/**
 * Tests the file serialization format.
 *
 * @group relaxed
 */
class FileItemNormalizerTest extends NormalizerTestBase{

  public static $modules = array(
    'serialization',
    'system',
    'entity',
    'field',
    'entity_test',
    'text',
    'filter',
    'user',
    'key_value',
    'multiversion',
    'rest',
    'uuid',
    'relaxed',
    'file',
    'image'
  );

  public $image;

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  protected function setUp() {
    parent::setUp();
    $this->installSchema('key_value', array('key_value_sorted'));
    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    $this->container
      ->get('entity.definition_update_manager')
      ->applyUpdates();

    // Create a File field for testing.
    entity_create('field_storage_config', array(
        'name' => 'field_test_file',
        'entity_type' => 'entity_test_mulrev',
        'type' => 'file',
        'cardinality' => 2,
        'translatable' => FALSE,
      ))->save();
    entity_create('field_config', array(
        'entity_type' => 'entity_test_mulrev',
        'field_name' => 'field_test_file',
        'bundle' => 'entity_test_mulrev',
        'label' => 'Test file-field',
        'widget' => array(
          'type' => 'file',
          'weight' => 0,
        ),
      ))->save();
    file_put_contents('public://example.txt', $this->randomMachineName());
    $this->file = entity_create('file', array(
        'uri' => 'public://example.txt',
      ));
    $this->file->save();

    // Create a Image field for testing.
    entity_create('field_storage_config', array(
        'name' => 'field_test_image',
        'entity_type' => 'entity_test_mulrev',
        'type' => 'image',
        'cardinality' => 3,
        'translatable' => FALSE,
      ))->save();
    entity_create('field_config', array(
        'entity_type' => 'entity_test_mulrev',
        'field_name' => 'field_test_image',
        'bundle' => 'entity_test_mulrev',
        'label' => 'Test image-field',
        'widget' => array(
          'type' => 'image',
          'weight' => 0,
        ),
      ))->save();
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = entity_create('file', array(
        'uri' => 'public://example.jpg',
      ));
    $this->image->save();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => 0,
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
      'field_test_file' => array(
        'target_id' => $this->file->id(),
        'display' => 1,
        'description' => $this->randomMachineName(),
      ),
      'field_test_image' => array(
        'target_id' => $this->file->id(),
        'display' => 1,
        'description' => $this->randomMachineName(),
        'alt' => $this->randomMachineName(),
        'title' => $this->randomMachineName(),
        'width' => 200,
        'height' => 100,
      ),
    );
    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testFileItemNormalize() {
    $entity = entity_load('entity_test_mulrev', $this->entity->id());

    $expected = array(
      'id' => array(
        array('value' => 1),
      ),
      'revision_id' => array(
        array('value' => 1),
      ),
      'uuid' => array(
        array('value' => $this->entity->uuid()),
      ),
      'langcode' => array(
        array('value' => 'en'),
      ),
      'name' => array(
        array('value' => $this->values['name']),
      ),
      'type' => array(
        array('value' => 'entity_test_mulrev'),
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
      'field_test_file' => array(
        array(
          'target_id' => $this->values['field_test_file']['target_id'],
          'display' => $this->values['field_test_file']['display'],
          'description' => $this->values['field_test_file']['description'],
        ),
      ),
      'field_test_image' => array(
        array(
          'target_id' => $this->values['field_test_image']['target_id'],
          'display' => $this->values['field_test_image']['display'],
          'description' => $this->values['field_test_image']['description'],
          'alt' => $this->values['field_test_image']['alt'],
          'title' => $this->values['field_test_image']['title'],
          'width' => $this->values['field_test_image']['width'],
          'height' => $this->values['field_test_image']['height'],
        ),
      ),
      '_local' => array(
        array(
          'value' => $entity->_local->first()->get('value')->getCastedValue(),
        )
      ),
      '_id' => $entity->uuid(),
      '_rev' => $entity->_revs_info->first()->get('rev')->getCastedValue(),
      '_deleted' => $entity->_deleted->first()->get('value')->getCastedValue(),
      '_local_seq' => $entity->_local_seq->first()->get('value')->getCastedValue(),
      '_entity_type' => $entity->getEntityTypeId(),
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

}