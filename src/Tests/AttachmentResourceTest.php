<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Tests the /db/doc/attachment.
 *
 * @group relaxed
 */
class AttachmentResourceTest extends ResourceTestBase {

  public static $modules = [
    'rest',
    'entity_test',
    'file',
    'image'
  ];

  /**
   * @var \Drupal\file\FileInterface[]
   */
  protected $files;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  protected function setUp() {
    parent::setUp();

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_rev', 'view');
    $permissions = array_merge($permissions, $this->entityPermissions('entity_test_rev', 'create'));
    $permissions = array_merge($permissions, $this->entityPermissions('entity_test_rev', 'delete'));
    $permissions[] = 'administer workspaces';
    $permissions[] = 'restful get relaxed:attachment';
    $permissions[] = 'restful put relaxed:attachment';
    $permissions[] = 'restful delete relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // We set this here just to test creation, saving and then getting
    // (with 'relaxed:attachment') entities on the same workspace.
    $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

    // Create a File field for testing.
    FieldStorageConfig::create([
        'field_name' => 'field_test_file',
        'entity_type' => 'entity_test_rev',
        'type' => 'file',
        'cardinality' => 4,
        'translatable' => FALSE,
      ])->save();
    FieldConfig::create([
        'entity_type' => 'entity_test_rev',
        'field_name' => 'field_test_file',
        'bundle' => 'entity_test_rev',
        'label' => 'Test file-field',
        'widget' => [
          'type' => 'file',
          'weight' => 0,
        ],
      ])->save();
    file_put_contents('public://example1.txt', $this->randomMachineName());
    $this->files['1'] = File::create(['uri' => 'public://example1.txt']);
    $this->files['1']->save();
    file_put_contents('public://example2.txt', $this->randomMachineName());
    $this->files['2'] = File::create(['uri' => 'public://example2.txt']);
    $this->files['2']->save();

    // Create a Image field for testing.
    FieldStorageConfig::create([
      'field_name' => 'field_test_image',
      'entity_type' => 'entity_test_rev',
      'type' => 'image',
      'cardinality' => 3,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_rev',
      'field_name' => 'field_test_image',
      'bundle' => 'entity_test_rev',
      'label' => 'Test image-field',
      'widget' => [
        'type' => 'image',
        'weight' => 0,
      ],
    ])->save();
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.png');
    $this->files['3'] = File::create(['uri' => 'public://example.png']);
    $this->files['3']->save();

    $values = [
      'name' => $this->randomMachineName(),
      'user_id' => 0,
      'field_test_file' => [
        [
          'target_id' => $this->files['1']->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ],
        [
          'target_id' => $this->files['2']->id(),
          'display' => 1,
          'description' => $this->randomMachineName(),
        ],
      ],
      'field_test_image' => [
        'target_id' => $this->files['3']->id(),
        'display' => 1,
        'description' => $this->randomMachineName(),
        'alt' => $this->randomMachineName(),
        'title' => $this->randomMachineName(),
        'width' => 200,
        'height' => 100,
      ],
    ];
    $this->entity = EntityTestRev::create($values);
    $this->entity->save();
  }

  public function testHead() {
    $this->enableService('relaxed:attachment', 'GET');

    $file_contents = file_get_contents($this->files['1']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();
    $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', 'text/plain; charset=UTF-8');
    $this->assertHeader('content-length', $this->files['1']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['2']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', 'text/plain; charset=UTF-8');
    $this->assertHeader('content-length', $this->files['2']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['3']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_image/0/' . $this->files['3']->uuid() . '/public/' . $this->files['3']->getFileName();
    $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->files['3']->getMimeType());
    $this->assertHeader('content-length', $this->files['3']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);
  }

  public function testGet() {
    $this->enableService('relaxed:attachment', 'GET');

    $file_contents = file_get_contents($this->files['1']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();
    $response = $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL, FALSE);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', 'text/plain; charset=UTF-8');
    $this->assertEqual($response, $file_contents);
    $this->assertHeader('content-length', $this->files['1']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['2']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $response = $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL, FALSE);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', 'text/plain; charset=UTF-8');
    $this->assertEqual($response, $file_contents);
    $this->assertHeader('content-length', $this->files['2']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['3']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_image/0/' . $this->files['3']->uuid() . '/public/' . $this->files['3']->getFileName();
    $response = $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL, FALSE);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->files['3']->getMimeType());
    $this->assertEqual($response, $file_contents);
    $this->assertHeader('content-length', $this->files['3']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);
  }

  public function testPut() {
    $this->enableService('relaxed:attachment', 'PUT');

    $serializer = $this->container->get('serializer');
    $file_uri = 'public://new_example.txt';
    file_put_contents($file_uri, $this->randomMachineName());
    $file_stub = File::create(['uri' => $file_uri]);
    $serialized = $serializer->serialize($file_stub, 'stream');

    $field_name = 'field_test_file';
    $attachment_info = $field_name . '/0/' . $file_stub->uuid() . '/public/' . $file_stub->getFileName();
    $response = $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'PUT', $serialized);
    $this->assertResponse('200', 'HTTP response code is correct');
    $data = Json::decode($response);
    $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

    /** @var \Drupal\file\FileInterface $file */
    $files = \Drupal::entityManager()->getStorage('file')->loadByProperties(array('uri' => $file_uri));
    $file = reset($files);
    $this->assertTrue(!empty($file), 'File was saved.');
    $this->assertEqual($file->getFileUri(), $file_uri, 'File was saved with the correct URI.');

    $entity = EntityTestRev::load($this->entity->id());
    $this->assertEqual($entity->{$field_name}->get(0)->target_id, $file->id(), 'File was attached to the entity.');
  }

  public function testDelete() {
    $this->enableService('relaxed:attachment', 'DELETE');

    $field_name = 'field_test_file';
    $attachment_info = $field_name . '/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $response = $this->httpRequest("$this->dbname/" . $this->entity->uuid() . "/$attachment_info", 'DELETE', NULL);
    $this->assertResponse('200', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $file = File::load($this->files['2']->id());
    $this->assertTrue(empty($file), 'The file was deleted.');
    $entity = EntityTestRev::load($this->entity->id());
    $this->assertEqual($entity->{$field_name}->count(), 1, 'The file does not exist on the entity any more.');
  }

}
