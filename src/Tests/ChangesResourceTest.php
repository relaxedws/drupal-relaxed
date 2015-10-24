<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\ChangesResourceTest.
 */

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

    $expected_with_docs = $expected_without_docs = array('last_seq' => NULL, 'results' => array());

    // Add info for the new created user.
    $account = entity_load('user', $account->id(), TRUE);
    $account_first_seq = $this->multiversionManager->lastSequenceId();
    $expected_without_docs['results'][] = array(
      'changes' => array(array('rev' => $account->_rev->value)),
      'id' => $account->uuid(),
      'seq' => $account_first_seq,
    );
    $expected_with_docs['results'][] = array(
      'changes' => array(array('rev' => $account->_rev->value)),
      'id' => $account->uuid(),
      'seq' => $account_first_seq,
      'doc' => $serializer->normalize($account)
    );

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
    $first_seq = $this->multiversionManager->lastSequenceId();
    $expected_without_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_rev->value)),
      'id' => $entity->uuid(),
      'seq' => $first_seq,
    );
    $expected_with_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_rev->value)),
      'id' => $entity->uuid(),
      'seq' => $first_seq,
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
    $second_seq = $this->multiversionManager->lastSequenceId();
    $expected_without_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_rev->value)),
      'id' => $entity->uuid(),
      'seq' => $second_seq,
      'deleted' => true,
    );
    $expected_with_docs['results'][] = array(
      'changes' => array(array('rev' => $entity->_rev->value)),
      'id' => $entity->uuid(),
      'seq' => $second_seq,
      'doc' => $serializer->normalize($entity),
      'deleted' => true,
    );

    $expected_with_docs['last_seq'] = $expected_without_docs['last_seq'] = $this->multiversionManager->lastSequenceId();

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

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('since' => $first_seq));
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // Unset first and second values from results, it shouldn't be returned when since == 3.
    unset($expected_without_docs['results'][0]);
    unset($expected_without_docs['results'][1]);
    // Reset the keys of the results array.
    $expected_without_docs['results'] = array_values($expected_without_docs['results']);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$db/_changes", 'GET', NULL, $this->defaultMimeType, NULL, array('since' => $second_seq));
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // The result array should be empty in this case.
    $expected_without_docs['results'] = array();
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    // @todo: {@link https://www.drupal.org/node/2600488 Assert the sort order.}
  }

}
