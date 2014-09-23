<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db resource.
 *
 * @group relaxed
 */
class DbResourceTest extends ResourceTestBase {

  public function testHead() {
    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:root:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest($this->workspace->name(), 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertTrue(empty($response), 'HEAD request returned no body.');
  }

  public function testGet() {
    $this->enableService('relaxed:root:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest($this->workspace->name(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    // Only assert one example property here, other properties should be
    // checked in serialization tests.
    $this->assertEqual($data['db_name'], $this->workspace->name(), 'GET request returned correct db_name.');
  }

  public function testPut() {
    $this->enableService('relaxed:root:db', 'PUT');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'create');
    $permissions[] = 'restful put relaxed:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $name = $this->randomMachineName();
    $response = $this->httpRequest($name, 'PUT', NULL);
    $this->assertResponse('201', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'PUT request returned ok.');

    $entity = \Drupal::entityManager()->loadEntityByUuid('workspace', $name);
    $this->assertTrue(!empty($entity), 'The entity being PUT was loaded.');

    $name = $this->randomMachineName();
    $entity = $this->createWorkspace($name);
    $entity->save();

    // Test putting an existing workspace.
    $response = $this->httpRequest($entity->name(), 'PUT', NULL);
    $this->assertResponse('412', 'HTTP response code is correct for existing database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['error']), 'PUT request returned error.');
  }

  public function testPost() {
    $this->enableService('relaxed:root:db', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful post relaxed:root:db';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest($this->workspace->name(), 'POST', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct when posting new entity');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'POST request returned a revision hash.');

      $response = $this->httpRequest($this->workspace->name(), 'POST', $serialized);
      $this->assertResponse('409', 'HTTP response code is correct when posting conflicting entity');
    }
  }

  public function testDelete() {
    $this->enableService('relaxed:root:db', 'DELETE');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'delete');
    $permissions[] = 'restful delete relaxed:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $name = $this->randomMachineName();
    $entity = $this->createWorkspace($name);
    $entity->save();

    $response = $this->httpRequest($entity->name(), 'DELETE', NULL);
    $this->assertResponse('200', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $entity = entity_load('workspace', $entity->id());
    $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');
  }

}
