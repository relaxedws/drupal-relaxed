<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_changes resource.
 *
 * @group relaxed
 */
class ChangesTest extends ResourceTestBase {

  public function testGet() {
    $db = $this->workspace->name();
    $this->enableService('relaxed:changes', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:changes';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entity = entity_create('entity_test_rev');
    $entity->save();

    // Update the field_test_text field.
    $entity->set(
      'field_test_text',
      array(
        0 => array(
          'value' => $this->randomString(),
          'format' => 'plain_text',
        ),
      )
    );
    $entity->save();

    // Update the name filed.
    $entity->set(
      'name',
      array(
        0 => array(
          'value' => $this->randomString(12),
          'format' => 'plain_text',
        ),
      )
    );
    $entity->save();

    // Update the name filed again.
    $entity->set(
      'name',
      array(
        0 => array(
          'value' => $this->randomString(25),
          'format' => 'plain_text',
        ),
      )
    );
    $entity->save();

    // Create a new entity.
    $entity = entity_create('entity_test_rev');
    $entity->save();

    // Update the field_test_text field.
    $entity->set(
      'field_test_text',
      array(
        0 => array(
          'value' => $this->randomString(),
          'format' => 'plain_text',
        ),
      )
    );
    $entity->save();

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertTrue(
      is_array($data) && !empty($data),
      'Data format is correct, the array is not empty.'
    );

  }
}
