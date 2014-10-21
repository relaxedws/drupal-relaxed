<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/doc resource.
 *
 * @group relaxed
 * @todo Test more entity types, at least node, taxonomy term, comment and user.
 */
class DocResourceTest extends ResourceTestBase {

  public function testHead() {
    $db = $this->workspace->id();

    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:doc', 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $this->httpRequest("$db/bogus", 'HEAD', NULL);
      $this->assertResponse('404', 'HTTP response code is correct for non-existing entities.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
      // @todo Change when a proper event handler is implemented for ETag.
      $this->assertHeader('x-relaxed-etag', $first_rev);
      $this->assertTrue(empty($response), 'HEAD request returned no body.');

      $new_name = $this->randomMachineName();
      $entity->name = $new_name;
      $entity->save();
      $second_rev = $entity->_revs_info->rev;

      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL, NULL, NULL, array('rev' => $first_rev));
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      // Test the response for a fake revision.
      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL, NULL, NULL, array('rev' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');

      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL, NULL, array('if-none-match' => $first_rev));
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL, NULL, array('if-none-match' => $second_rev));
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      // Test the response for a fake revision using if-none-match header.
      $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL, NULL, array('if-none-match' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');
    }
  }

  public function testGet() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:doc', 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $this->httpRequest("$db/bogus", 'GET', NULL);
      $this->assertResponse('404', 'HTTP response code is correct for non-existing entities.');

      $entity = entity_create($entity_type);
      $entity->save();

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      // @todo Change when a proper event handler is implemented for ETag.
      $this->assertHeader('x-relaxed-etag', $entity->_revs_info->rev);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['_rev'], $entity->_revs_info->rev, 'GET request returned correct revision hash.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('revs' => TRUE));
      $data = Json::decode($response);
      $rev = $data['_revisions']['start'] . '-' . $data['_revisions']['ids'][0];
      $this->assertEqual($rev, $entity->_revs_info->rev, 'GET request returned revision list.');

      // Save an additional revision.
      $entity->save();

      $open_revs = array();
      foreach ($entity->_revs_info as $item) {
        $open_revs[] = $item->rev;
      }
      $open_revs_string = '[' . implode(',', $open_revs) . ']';
      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('open_revs' => $open_revs_string));

      // Test the response for a fake revision.
      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('rev' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_revs_info->rev;

      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, array('if-none-match' => $first_rev));
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $first_rev);

      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, array('if-none-match' => $second_rev));
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertHeader('x-relaxed-etag', $second_rev);

      // Test the response for a fake revision using if-none-match header.
      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, array('if-none-match' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');
    }
  }

  public function testPut() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:doc', 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful put relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_revs_info->rev;
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, array('if-match' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, array('if-match' => $second_rev));
      $this->assertResponse('201', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = entity_load($entity_type, $entity->id());

      $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, array('rev' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, array('rev' => $entity->_revs_info->rev));
      $this->assertResponse('201', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');
    }
  }

  public function testDelete() {
    $db = $this->workspace->id();

    $this->enableService('relaxed:doc', 'DELETE');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'delete');
      $permissions[] = 'restful delete relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL);
      $this->assertResponse('200', 'HTTP response code is correct for new database');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

      $entity = entity_load($entity_type, $entity->id());
      $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_revs_info->rev;

      $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, array('rev' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, array('rev' => $second_rev));
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

      // Test the response for a fake revision.
      $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, NULL, array('rev' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_revs_info->rev;

      $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, array('if-match' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, array('if-match' => $second_rev));
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');
    }
  }
}
