<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/doc resource.
 *
 * @group relaxed
 * @todo Test more entity types, at least node, taxonomy term, comment and user.
 */
class LocalDocResourceTest extends ResourceTestBase {

  public function testHead() {
    $db = $this->workspace->id();

    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:local:doc', 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->_local->value = FALSE;
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity->_local->value = TRUE;
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
    }
  }

  public function testGet() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:local:doc', 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->_local->value = FALSE;
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'GET', NULL);
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity->_local->value = TRUE;
      $entity->save();
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'GET', NULL);
      $this->assertResponse('200', 'HTTP response code is correct.');
    }
  }

  public function testPut() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:local:doc', 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful put relaxed:local:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->_local->value = FALSE;
      $serialized = $serializer->serialize($entity, $this->defaultFormat);
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('400', 'HTTP response code is correct');

      $entity = entity_create($entity_type);
      $entity->_local->value = NULL;
      $serialized = $serializer->serialize($entity, $this->defaultFormat);
      $this->httpRequest("$db/_local/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct');
      $entity = \Drupal::entityManager()->loadEntityByUuid('entity_test_rev', $entity->uuid());
      $this->assertTrue($entity->_local->value, 'The field was saved with the correct value');
    }
  }

}
