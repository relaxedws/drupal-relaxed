<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\Normalizer\FileItemNormalizerTest.
 */

namespace Drupal\Tests\relaxed\Unit\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Tests the file serialization format.
 *
 * @group relaxed
 */
class FileItemNormalizerTest extends NormalizerTestBase{

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
    'relaxed',
    'file',
    'image'
  ];

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    // Create a File field for testing.
    FieldStorageConfig::create([
      'field_name' => 'field_test_file',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'file',
      'cardinality' => 2,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_file',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test file-field',
      'widget' => [
        'type' => 'file',
        'weight' => 0,
      ],
    ])->save();
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testNormalizer() {
    // Create two txt files.
    file_put_contents('public://example1.txt', $this->randomMachineName());
    $file1 = File::create(['uri' => 'public://example1.txt']);
    $file1->save();
    file_put_contents('public://example2.txt', $this->randomMachineName());
    $file2 = File::create(['uri' => 'public://example2.txt']);
    $file2->save();

    // Create a Image field for testing.
    FieldStorageConfig::create([
      'field_name' => 'field_test_image',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'image',
      'cardinality' => 3,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_image',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test image-field',
      'widget' => [
        'type' => 'image',
        'weight' => 0,
      ],
    ])->save();

    // Create a jpg file.
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $file3 = File::create(['uri' => 'public://example.jpg']);
    $file3->save();

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
          'target_id' => $file1->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ),
        array(
          'target_id' => $file2->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ),
      ),
      'field_test_image' => array(
        'target_id' => $file3->id(),
        'display' => 1,
        'description' => $this->randomMachineName(),
        'alt' => $this->randomMachineName(),
        'title' => $this->randomMachineName(),
        'width' => 200,
        'height' => 100,
      ),
    );
    $this->entity = EntityTestMulRev::create($this->values);
    $this->entity->save();

    // Test normalize.
    $entity = EntityTestMulRev::load($this->entity->id());
    $attachments_keys['1'] = 'field_test_file/0/' . $file1->uuid() . '/public/' . $file1->getFileName();
    $attachments_keys['2'] = 'field_test_file/1/' . $file2->uuid() . '/public/' . $file2->getFileName();
    $attachments_keys['3'] = 'field_test_image/0/' . $file3->uuid() . '/public/' . $file3->getFileName();
    $expected_attachments = [];
    $files_number = 1;
    while ($files_number <= 3) {
      $file = "file$files_number";
      $uri = $$file->getFileUri();
      $file_contents = file_get_contents($uri);
      $attachments = [
        $attachments_keys[$files_number] => [
          'content_type' => $$file->getMimeType(),
          'digest' => 'md5-' . base64_encode(md5($file_contents)),
          'length' => $$file->getSize(),
          'data' => base64_encode($file_contents),
        ],
      ];
      $expected_attachments = array_merge($expected_attachments, $attachments);
      $files_number++;
    }

    $expected = array(
      '@context' => array(
        '_id' => '@id',
        'entity_test_mulrev' => \Drupal::service('rest.link_manager')->getTypeUri(
          'entity_test_mulrev',
          $this->entity->bundle()
        ),
        '@language' => 'en'
      ),
      '@type' => 'entity_test_mulrev',
      'en' => [
        '@context' => [
          '@language' => 'en',
        ],
        'id' => [
          ['value' => $this->entity->id()],
        ],
        'uuid' => [
          ['value' => $this->entity->uuid()],
        ],
        'langcode' => [
          ['value' => 'en'],
        ],
        'name' => [
          ['value' => $this->values['name']],
        ],
        'type' => [
          ['value' => 'entity_test_mulrev'],
        ],
        'created' => [
          ['value' => $this->entity->created->value],
        ],
        'default_langcode' => [
          ['value' => TRUE],
        ],
        'user_id' => [
          ['target_id' => $this->values['user_id']],
        ],
        'revision_id' => [
          ['value' => $this->entity->getRevisionId()],
        ],
        'workspace' => [
          [
            'entity_type_id' => $this->entity->get('workspace')->entity->getEntityTypeId(),
            'target_uuid' => $this->entity->get('workspace')->entity->uuid(),
          ]
        ],
        '_deleted' => [
          ['value' => FALSE],
        ],
        '_rev' => [
          ['value' => $this->entity->_rev->value],
        ],
        'field_test_text' => [
          [
            'value' => $this->values['field_test_text']['value'],
            'format' => $this->values['field_test_text']['format'],
          ],
        ],
      ],
      '_attachments' => $expected_attachments,
      '_id' => $entity->uuid(),
      '_rev' => $entity->_rev->value,
    );

    $normalized = $this->serializer->normalize($this->entity);
    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertSame($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertSame($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');
  }

}
