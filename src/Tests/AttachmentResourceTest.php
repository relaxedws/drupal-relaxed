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
        'target_id' => $this->image->id(),
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

  public function testHead() {
    $db = $this->workspace->id();

    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:attachment', 'GET');
    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_mulrev', 'view');
    $permissions[] = 'restful get relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();

    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');

    $this->assertHeader('content-type', $this->defaultMimeType);
  }

  public function testGet() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:attachment', 'GET');
    $serializer = $this->container->get('serializer');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('entity_test_mulrev', 'view');
    $permissions[] = 'restful get relaxed:attachment';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $attachment_info = 'field_test_file/0/' . $this->files['1']->uuid() . '/public/' . $this->files['1']->getFileName();

    $response = $this->httpRequest("$db/" . $this->entity->uuid() . "/$attachment_info", 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');

    $this->assertHeader('content-type', $this->defaultMimeType);
  }
}