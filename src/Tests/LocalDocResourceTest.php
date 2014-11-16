<?php

namespace Drupal\relaxed\Tests;

/**
 * Tests the /db/doc resource.
 *
 * @group relaxed
 */
class LocalDocResourceTest extends ResourceTestBase {

  public function testHead() {
    $db = $this->workspace->id();

    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:local:doc', 'GET');
    $entity_types = array('entity_test_local');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
    }

    // Test with an entity type that is not local.
    $entity = entity_create('entity_test_rev');
    $entity->save();
    $this->httpRequest("$db/_local/" . $entity->uuid(), 'HEAD', NULL);
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertResponse('400', 'HTTP response code is correct.');
  }

  public function testGet() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:local:doc', 'GET');
    $entity_types = array('entity_test_local');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'GET', NULL);
      $this->assertResponse('200', 'HTTP response code is correct.');
    }

    // Test with an entity type that is not local.
    $entity = entity_create('entity_test_rev');
    $entity->save();
    $this->httpRequest("$db/_local/" . $entity->uuid(), 'GET', NULL);
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertResponse('400', 'HTTP response code is correct.');
  }

  public function testPut() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:local:doc', 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = array('entity_test_local');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful put relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct');
    }

    // Test with an entity type that is not local.
    $entity = entity_create('entity_test_rev');
    $serialized = $serializer->serialize($entity, $this->defaultFormat);
    $this->httpRequest("$db/_local/" . $entity->uuid(), 'PUT', $serialized);
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertResponse('400', 'HTTP response code is correct.');
  }

}
