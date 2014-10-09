<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/doc/attachment.
 *
 * @group relaxed
 */
class AttachmentResourceTest extends ResourceTestBase {

  public static $modules = array('file', 'image');

  protected function setUp() {
    parent::setUp();

    // Create a File field for testing.
    entity_create('field_storage_config', array(
        'field_name' => 'field_test_file',
        'entity_type' => 'entity_test_rev',
        'type' => 'file',
        'cardinality' => 2,
        'translatable' => FALSE,
      ))->save();
    entity_create('field_config', array(
        'entity_type' => 'entity_test_rev',
        'field_name' => 'field_test_file',
        'bundle' => 'entity_test_rev',
        'label' => 'Test file-field',
        'widget' => array(
          'type' => 'file',
          'weight' => 0,
        ),
      ))->save();
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
        'entity_type' => 'entity_test_rev',
        'type' => 'image',
        'cardinality' => 3,
        'translatable' => FALSE,
      ))->save();
    entity_create('field_config', array(
        'entity_type' => 'entity_test_rev',
        'field_name' => 'field_test_image',
        'bundle' => 'entity_test_rev',
        'label' => 'Test image-field',
        'widget' => array(
          'type' => 'image',
          'weight' => 0,
        ),
      ))->save();
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.png');
    $this->files['3'] = entity_create('file', array(
        'uri' => 'public://example.png',
      ));
    $this->files['3']->save();

    $this->values = array(
      'name' => $this->randomMachineName(),
      'user_id' => 0,
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
    $this->entity = entity_create('entity_test_rev', $this->values);
    $this->entity->save();

  }

  public function testHead() {
    $db = $this->workspace->id();

    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:attachment', 'GET');
    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_rev', 'view');
    $permissions[] = 'restful get relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $file_contents = file_get_contents($this->files['1']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-length', $this->files['1']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['2']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-length', $this->files['2']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['3']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_image/0/' . $this->files['3']->uuid() . '/public/' . $this->files['3']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-length', $this->files['3']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);
  }

  public function testGet() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:attachment', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_rev', 'view');
    $permissions[] = 'restful get relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $file_contents = file_get_contents($this->files['1']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->files['1']->getMimeType());
    $this->assertHeader('content-length', $this->files['1']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['2']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->files['2']->getMimeType());
    $this->assertHeader('content-length', $this->files['2']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);

    $file_contents = file_get_contents($this->files['3']->getFileUri());
    $encoded_digest = base64_encode(md5($file_contents));

    $attachment_info = 'field_test_image/0/' . $this->files['3']->uuid() . '/public/' . $this->files['3']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->files['3']->getMimeType());
    $this->assertHeader('content-length', $this->files['3']->getSize());
    $this->assertHeader('x-relaxed-etag', $encoded_digest);
    $this->assertHeader('content-md5', $encoded_digest);
  }

  public function testPut() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:attachment', 'PUT');
    $serializer = $this->container->get('serializer');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_rev', 'view');
    $permissions[] = 'restful put relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    file_put_contents('public://new_example.txt', $this->randomMachineName());
    $file = entity_create('file', array(
        'uri' => 'public://new_example.txt',
      ));
    $serialized = $serializer->serialize($file, $this->defaultFormat);

    $attachment_info = 'field_test_file/0/' . $file->uuid() . '/public/' . $file->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'PUT', $serialized);
    $this->assertResponse('201', 'HTTP response code is correct');
    $data = Json::decode($response);
    $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');
  }

  public function testDelete() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:attachment', 'DELETE');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_rev', 'delete');
    $permissions[] = 'restful delete relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $attachment_info = 'field_test_file/1/' . $this->files['2']->uuid() . '/public/' . $this->files['2']->getFileName();
    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'DELETE', NULL);
    $this->assertResponse('200', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $entity = entity_load('file',  $this->files['2']->id());
    $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');
  }
}