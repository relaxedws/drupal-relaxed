<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\relaxed\HttpMultipart\Message\MultipartResponse;
use GuzzleHttp\Psr7;

/**
 * Tests the /db/doc resource.
 *
 * @group relaxed
 * @todo {@link https://www.drupal.org/node/2600490 Test more entity types.}
 */
class DocResourceTest extends ResourceTestBase {

  public function testHead() {
    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:doc', 'GET');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $this->httpRequest("$this->dbname/bogus", 'HEAD', NULL);
      $this->assertResponse('404', 'HTTP response code is correct for non-existing entities.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();
      $first_rev = $entity->_rev->value;

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('x-relaxed-etag', $first_rev);
      $this->assertTrue(empty($response), 'HEAD request returned no body.');

      $new_name = $this->randomMachineName();
      $entity->name = $new_name;
      $entity->save();
      $second_rev = $entity->_rev->value;

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL, NULL, NULL, ['rev' => $first_rev]);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      // Test the response for a fake revision.
      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL, NULL, NULL, ['rev' => '11112222333344445555']);
      $this->assertResponse('404', 'HTTP response code is correct.');

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL, NULL, ['if-none-match' => $first_rev]);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL, NULL, ['if-none-match' => $second_rev]);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      // Test the response for a fake revision using if-none-match header.
      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'HEAD', NULL, NULL, array('if-none-match' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');
    }
  }

  /**
   * Tests non-multipart GET requests.
   */
  public function testGet() {
    $this->enableService('relaxed:doc', 'GET');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $this->httpRequest("$this->dbname/bogus", 'GET', NULL);
      $this->assertResponse('404', 'HTTP response code is correct for non-existing entities.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $entity->_rev->value);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['_rev'], $entity->_rev->value, 'GET request returned correct revision hash.');

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, NULL, ['revs' => TRUE]);
      $data = Json::decode($response);
      $rev = $data['_revisions']['start'] . '-' . $data['_revisions']['ids'][0];
      $this->assertEqual($rev, $entity->_rev->value, 'GET request returned correct revision list after first revision.');

      // Save an additional revision.
      $entity->save();

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, NULL, ['revs' => TRUE]);
      $data = Json::decode($response);
      $count = count($data['_revisions']['ids']);
      $this->assertEqual($count, 2, 'GET request returned correct revision list after second revision.');

      // Test the response for a fake revision.
      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, NULL, ['rev' => '11112222333344445555']);
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, ['if-none-match' => $first_rev]);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, ['if-none-match' => $second_rev]);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      // Test the response for a fake revision using if-none-match header.
      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'GET', NULL, NULL, ['if-none-match' => '11112222333344445555']);
      $this->assertResponse('404', 'HTTP response code is correct.');
    }
  }

  /**
   * Tests GET requests with multiple parts.
   */
  public function testGetOpenRevs() {
    $this->enableService('relaxed:doc', 'GET', 'mixed');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();

      $entity->name = $this->randomMachineName();

      $open_revs = [];
      $open_revs[] = $entity->_rev->value;

      $open_revs_string = json_encode($open_revs);
      $response = $this->httpRequest(
        "$this->dbname/" . $entity->uuid(),
        'GET',
        NULL,
        'multipart/mixed',
        NULL,
        ['open_revs' => $open_revs_string, '_format' => 'mixed']
      );

      $stream = Psr7\stream_for($response);
      $parts = MultipartResponse::parseMultipartBody($stream);
      $this->assertResponse('200', 'HTTP response code is correct.');

      $data = [];
      foreach ($parts as $part) {
        $data[] = Json::decode($part['body']);
      }

      $correct_data = TRUE;
      foreach ($open_revs as $key => $rev) {
        if (isset($data[$key]['_rev']) && $data[$key]['_rev'] != $rev) {
          $correct_data = FALSE;
        }
      }
      $this->assertTrue($correct_data, 'Multipart response contains correct revisions.');

      // Test a non-multipart request with open_revs.
      $response = $this->httpRequest(
        "$this->dbname/" . $entity->uuid(),
        'GET',
        NULL,
        $this->defaultMimeType,
        NULL,
        ['open_revs' => $open_revs_string]
      );
      $data = Json::decode($response);
      $correct_data = TRUE;
      foreach ($open_revs as $key => $rev) {
        if (isset($data[$key]['ok']['_rev']) && $data[$key]['ok']['_rev'] != $rev) {
          $correct_data = FALSE;
        }
      }
      $this->assertTrue($correct_data, 'Response contains correct revisions.');
    }
  }

  public function testPut() {
    $this->enableService('relaxed:doc', 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful put relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $entity = $this->entityTypeManager->getStorage($entity_type)->create(['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create(['user_id' => $account->id()]);
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'PUT', $serialized, NULL, ['if-match' => $first_rev]);
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'PUT', $serialized, NULL, ['if-match' => $second_rev]);
      $this->assertResponse('201', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity->id());
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, ['rev' => $first_rev]);
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, ['rev' => $entity->_rev->value]);
      $this->assertResponse('201', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');
    }
  }

  public function testDelete() {
    $this->enableService('relaxed:doc', 'DELETE');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'delete');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful delete relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL);
      $this->assertResponse('200', 'HTTP response code is correct for new database');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity->id());
      $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, ['rev' => $first_rev]);
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, ['rev' => $second_rev]);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

      // Test the response for a fake revision.
      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, ['rev' => '11112222333344445555']);
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

      $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL, NULL, ['if-match' => $first_rev]);
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$this->dbname/" . $entity->uuid(), 'DELETE', NULL, NULL, ['if-match' => $second_rev]);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');
    }
  }

  public function testStub() {
    $this->enableService('relaxed:doc', 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type_id) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type_id, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful put relaxed:doc';
      $permissions[] = 'administer users';
      $permissions[] = 'administer taxonomy';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just for testing.
      $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

      $entity_uuid = 'fe36b529-e2d7-4625-9b07-7ee8f84928b2';
      $reference_uuid = '0aec21a0-8e36-11e5-8994-feff819cdc9f';

      $normalized = [
        '@context' => [
          '@id' => '_id',
          '@language' => 'en'
        ],
        '@type' => $entity_type_id,
        '_id' => $entity_uuid,
        'en' => [
          'name' => [],
          'type' => [['value' => $entity_type_id]],
          'created' => [['value' => 1447877434]],
          'user_id' => [],
          'tags_list' => [[
            'entity_type_id' => 'taxonomy_term',
            'target_uuid' => $reference_uuid,
          ]],
          'default_langcode' => [['value' => TRUE]],
        ],
      ];

      $response = $this->httpRequest("$this->dbname/" . $entity_uuid, 'PUT', Json::encode($normalized));
      $data = Json::decode($response);
      $this->assertResponse('201', 'HTTP response code is correct');
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $storage = $this->entityManager->getStorage('taxonomy_term');
      $referenced_terms = $storage->loadByProperties(['uuid' => $reference_uuid]);
      /** @var \Drupal\taxonomy\TermInterface $referenced_term */
      $referenced_term = reset($referenced_terms);

      $this->assertTrue(!empty($referenced_term), 'Referenced term way created.');
      $this->assertTrue($referenced_term->_rev->is_stub, 'References term was saved as stub.');

      $new_name = $this->randomMachineName();
      $referenced_term->name->value = $new_name;
      $serialized = $serializer->serialize($referenced_term, $this->defaultFormat);
      $response = $this->httpRequest("$this->dbname/" . $reference_uuid, 'PUT', $serialized);
      $data = Json::decode($response);
      $this->assertResponse('201', 'HTTP response code is correct');
      $this->assertNotEqual('0-00000000000000000000000000000000', $data['rev'], 'PUT request returned a revision hash.');

      $referenced_terms = $storage->loadByProperties(['uuid' => $reference_uuid]);
      /** @var \Drupal\taxonomy\TermInterface $referenced_term */
      $referenced_term = reset($referenced_terms);
      $this->assertEqual($new_name, $referenced_term->name->value, 'The name was updated successfully.');
    }
  }

}
