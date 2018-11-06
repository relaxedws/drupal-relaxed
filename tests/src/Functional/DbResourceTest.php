<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db resource.
 *
 * @group relaxed
 */
class DbResourceTest extends ResourceTestBase {

  public function testHead() {
    // HEAD and GET is handled by the same resource.

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($this->dbname, 'HEAD', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $this->assertTrue(empty((string) $response->getBody()), 'HEAD request returned no body.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($this->dbname, 'HEAD', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
  }

  public function testGet() {
    // Create a user with the correct permissions.
    $permissions[] = 'administer workspaces';
    $permissions[] = 'perform pull replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Add an entity to the workspace to test the update_seq property.
    $entity = $this->entityTypeManager->getStorage('entity_test_rev')->create();
    $entity->save();
    $entity->name = $this->randomMachineName();
    $entity->save();

    $response = $this->httpRequest($this->dbname, 'GET', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode((string) $response->getBody());
    // Only assert one example property here, other properties should be
    // checked in serialization tests.
    $this->assertEquals($data['db_name'], $this->dbname, 'GET request returned correct db_name.');

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($this->dbname, 'GET', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($this->dbname, 'GET', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
  }

  public function testPut() {
    // Create a user with the correct permissions.
    $permissions[] = 'administer workspaces';
    $permissions[] = 'perform push replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Test using an invalid machine name
    $id = 'A!"£%^&*{}#~@?';
    $response = $this->httpRequest($id, 'PUT', NULL);
    $this->assertSame($response->getStatusCode(), 400, 'HTTP response code is correct for missing database.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);

    $id = strtolower($this->randomMachineName());
    $response = $this->httpRequest($id, 'PUT', NULL);
    $this->assertSame($response->getStatusCode(), 201, 'HTTP response code is correct for new database.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode((string) $response->getBody());
    $this->assertTrue(!empty($data['ok']), 'PUT request returned ok.');

    $id = strtolower($this->randomMachineName());
    $entity = $this->createWorkspace($id);
    $entity->save();

    // Test putting an existing workspace.
    $response = $this->httpRequest($entity->id(), 'PUT', NULL);
    $this->assertSame($response->getStatusCode(), 412, 'HTTP response code is correct for existing database.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode((string) $response->getBody());
    $this->assertTrue(!empty($data['error']), 'PUT request returned error.');

    // Create a new ID.
    $id = strtolower($this->randomMachineName());

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($id, 'PUT', NULL);
    $this->assertSame($response->getStatusCode(), 403, 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 201.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($id, 'PUT', NULL);
    $this->assertSame($response->getStatusCode(), 201, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
  }

  public function testPost() {
    $serializer = $this->container->get('relaxed.serializer');

    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'perform push replication';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->create(['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest($this->dbname, 'POST', $serialized);
      $this->assertSame($response->getStatusCode(), 201, 'HTTP response code is correct when posting new entity.');
      $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
      $data = Json::decode((string) $response->getBody());
      $this->assertTrue(isset($data['rev']), 'POST request returned a revision hash.');

      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->create(['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      // Create a user with the 'perform pull replication' permission and test the
      // response code. It should be 403.
      $account = $this->drupalCreateUser(['perform pull replication']);
      $this->drupalLogin($account);
      $response = $this->httpRequest($this->dbname, 'POST', $serialized);
      $this->assertSame($response->getStatusCode(), 403, 'HTTP response code is correct.');

      // Create a user with the 'perform push replication' permission and test the
      // response code. It should be 201.
      $account = $this->drupalCreateUser(['perform push replication']);
      $this->drupalLogin($account);
      $response = $this->httpRequest($this->dbname, 'POST', $serialized);
      $this->assertSame($response->getStatusCode(), 201, 'HTTP response code is correct.');
      $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    }
  }

  public function testDelete() {
    // Create a user with the correct permissions.
    $permissions[] = 'administer workspaces';
    $permissions[] = 'perform push replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $id = $this->randomMachineName();
    $entity = $this->createWorkspace($id);
    $entity->save();

    $response = $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode((string) $response->getBody());
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $entity = $this->entityTypeManager
      ->getStorage('workspace')
      ->load($entity->id());
    $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');

    // Create a new workspace.
    $id = $this->randomMachineName();
    $entity = $this->createWorkspace($id);
    $entity->save();

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertSame($response->getStatusCode(), 403, 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $response = $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
  }

}
