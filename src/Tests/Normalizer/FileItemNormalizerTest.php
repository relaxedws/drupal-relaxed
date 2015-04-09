<?php

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Component\Utility\String;

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
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file = array();

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    // Create a File field for testing.
    entity_create('field_storage_config', array(
        'field_name' => 'field_test_file',
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

    // Create two txt files.
    file_put_contents('public://example1.txt', $this->randomMachineName());
    $this->files['1'] = entity_create('file', array(
        'uri' => 'public://example1.txt',
      ));
    $this->files['1']->save();
    file_put_contents('public://example2.txt', $this->randomMachineName());
    $this->files['2'] = entity_create('file', array(
        'uri' => 'public://example2.txt',
      ));
    $this->files['2']->save();

    // Create a Image field for testing.
    entity_create('field_storage_config', array(
        'field_name' => 'field_test_image',
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

    // Create a jpg file.
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->files['3'] = entity_create('file', array(
        'uri' => 'public://example.jpg',
      ));
    $this->files['3']->save();

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => 0,
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
      'field_test_file' => array(
        array(
          'target_id' => $this->files['1']->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ),
        array(
          'target_id' => $this->files['2']->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ),
      ),
      'field_test_image' => array(
        'target_id' => $this->files['3']->id(),
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
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testFileItemNormalize() {
    $entity = entity_load('entity_test_mulrev', $this->entity->id());

    $attachments_keys['1'] = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();
    $attachments_keys['2'] = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $attachments_keys['3'] = 'field_test_image/0/' . $this->files['3']->uuid() . '/public/' . $this->files['3']->getFileName();
    $expected_attachments = array();
    $files_number = 1;
    while ($files_number <= 3) {
      $uri = $this->files[$files_number]->getFileUri();
      $file_contents = file_get_contents($uri);
      $attachments = array(
        $attachments_keys[$files_number] => array(
          'content_type' => $this->files[$files_number]->getMimeType(),
          'digest' => 'md5-' . base64_encode(md5($file_contents)),
          'length' => $this->files[$files_number]->getSize(),
          'data' => base64_encode($file_contents),
        ),
      );
      $expected_attachments = array_merge($expected_attachments, $attachments);
      $files_number++;
    }

    $expected = array(
      '@context' => array(
        'entity_test_mulrev' => \Drupal::service('rest.link_manager')->getTypeUri(
          'entity_test_mulrev',
          $entity->bundle()
        ),
      ),
      '@id' => $this->getEntityUri($entity),
      '@type' => 'entity_test_mulrev',
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
      '_attachments' => $expected_attachments,
      'workspace' => array(
        array('target_id' => 'default')
      ),
      '_id' => $entity->uuid(),
      '_rev' => $entity->_revs_info->first()->get('rev')->getCastedValue(),
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $key) {
      $this->assertEqual($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');
  }

  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, String::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertIdentical($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertIdentical($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');
  }
}
