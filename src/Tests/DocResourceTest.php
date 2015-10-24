<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\DocResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
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
      $first_rev = $entity->_rev->value;

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'HEAD', NULL);
      $this->assertHeader('content-type', $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('x-relaxed-etag', $first_rev);
      $this->assertTrue(empty($response), 'HEAD request returned no body.');

      $new_name = $this->randomMachineName();
      $entity->name = $new_name;
      $entity->save();
      $second_rev = $entity->_rev->value;

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

  /**
   * Tests non-multipart GET requests.
   */
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
      $this->assertHeader('x-relaxed-etag', $entity->_rev->value);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['_rev'], $entity->_rev->value, 'GET request returned correct revision hash.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('revs' => TRUE));
      $data = Json::decode($response);
      $rev = $data['_revisions']['start'] . '-' . $data['_revisions']['ids'][0];
      $this->assertEqual($rev, $entity->_rev->value, 'GET request returned correct revision list after first revision.');

      // Save an additional revision.
      $entity->save();

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('revs' => TRUE));
      $data = Json::decode($response);
      $count = count($data['_revisions']['ids']);
      $this->assertEqual($count, 2, 'GET request returned correct revision list after second revision.');

      // Test the response for a fake revision.
      $this->httpRequest("$db/" . $entity->uuid(), 'GET', NULL, NULL, NULL, array('rev' => '11112222333344445555'));
      $this->assertResponse('404', 'HTTP response code is correct.');

      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

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

  /**
   * Tests GET requests with multiple parts.
   */
  public function testGetOpenRevs() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:doc', 'GET', 'mixed');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = 'restful get relaxed:doc';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();

      $entity->name = $this->randomMachineName();

      $open_revs = array();
      $open_revs[] = $entity->_rev->value;

      $open_revs_string = json_encode($open_revs);
      $response = $this->httpRequest(
        "$db/" . $entity->uuid(),
        'GET',
        NULL,
        'multipart/mixed',
        NULL,
        array('open_revs' => $open_revs_string, '_format' => 'mixed')
      );

      $stream = Psr7\stream_for($response);
      $parts = MultipartResponse::parseMultipartBody($stream);
      $this->assertResponse('200', 'HTTP response code is correct.');

      $data = array();
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
        "$db/" . $entity->uuid(),
        'GET',
        NULL,
        $this->defaultMimeType,
        NULL,
        array('open_revs' => $open_revs_string)
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

      $entity = entity_create($entity_type, ['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = entity_create($entity_type, ['user_id' => $account->id()]);
      $entity->save();
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, array('if-match' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, array('if-match' => $second_rev));
      $this->assertResponse('201', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');

      $entity = entity_load($entity_type, $entity->id(), TRUE);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, array('rev' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'PUT', $serialized, NULL, NULL, array('rev' => $entity->_rev->value));
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
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

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
      $first_rev = $entity->_rev->value;
      $entity->name = $this->randomMachineName();
      $entity->save();
      $second_rev = $entity->_rev->value;

      $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, array('if-match' => $first_rev));
      $this->assertResponse('409', 'HTTP response code is correct.');

      $response = $this->httpRequest("$db/" . $entity->uuid(), 'DELETE', NULL, NULL, array('if-match' => $second_rev));
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');
    }
  }

}
