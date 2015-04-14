<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_changes resource.
 *
 * @group relaxed
 */
class ChangesResourceTest extends ResourceTestBase {

  public function testGet() {
    $db = $this->workspace->id();
    $serializer = \Drupal::service('serializer');
    $this->enableService('relaxed:changes', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:changes';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $expected_with_docs = $expected_without_docs = array('last_seq' => 6, 'results' => array());

    $entity = entity_create('entity_test_rev');
    $entity->save();
    // Update the field_test_text field.
    $entity->set('field_test_text', array(array('value' => $this->randomString(), 'format' => 'plain_text')));
    $entity->save();

    // Update the name filed.
    $entity->set('name', array(array('value' => $this->randomString(12), 'format' => 'plain_text')));
    $entity->save();

    // Update the name filed again.
    $entity->set('name', array(array('value' => $this->randomString(25), 'format' => 'plain_text')));
    $entity->save();
    $expected_without_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_revs_info->rev)),
      'id' => $entity->uuid(),
      'seq' => 3,
    );
    $expected_with_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_revs_info->rev)),
      'id' => $entity->uuid(),
      'seq' => 3,
      'doc' => $serializer->normalize($entity)
    );

    // Create a new entity.
    $entity = entity_create('entity_test_rev');
    $entity->save();

    // Update the field_test_text field.
    $entity->set('field_test_text', array(array('value' => $this->randomString(), 'format' => 'plain_text')));
    $entity->save();

    // Delete the entity.
    $entity->delete();
    $expected_without_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_revs_info->rev)),
      'id' => $entity->uuid(),
      'seq' => 6,
      'deleted' => true,
    );
    $expected_with_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_revs_info->rev)),
      'id' => $entity->uuid(),
      'seq' => 6,
      'doc' => $serializer->normalize($entity),
      'deleted' => true,
    );

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('include_docs' => 'true'));
    $this->assertResponse('200', 'HTTP response code is correct when including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_with_docs, 'The result is correct when including docs.');

    // Test when using 'since' query parameter.
    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('since' => 1));
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('since' => 3));
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // Unset first value from results, it shouldn't be returned when since == 3.
    unset($expected_without_docs['results'][0]);
    // Reset the keys of the results array.
    $expected_without_docs['results'] = array_values($expected_without_docs['results']);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('since' => 6));
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // The result array should be empty in this case.
    $expected_without_docs['results'] = array();
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');
  }

}
