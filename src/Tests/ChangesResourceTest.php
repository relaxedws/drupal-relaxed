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
    $serializer = \Drupal::service('serializer');
    $this->enableService('relaxed:changes', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'administer workspaces';
    $permissions[] = 'restful get relaxed:changes';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // We set this here just to test creation, saving and then getting
    // (with 'relaxed:changes') changes on the same workspace.
    $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

    $expected_with_docs = $expected_without_docs = ['last_seq' => NULL, 'results' => []];

    $entity = $this->entityTypeManager->getStorage('entity_test_rev')->create();
    $entity->save();
    // Update the field_test_text field.
    $entity->set('field_test_text', [['value' => $this->randomString(), 'format' => 'plain_text']]);
    $entity->save();

    // Update the name filed.
    $entity->set('name', [['value' => $this->randomString(12), 'format' => 'plain_text']]);
    $entity->save();

    // Update the name filed again.
    $entity->set('name', [['value' => $this->randomString(25), 'format' => 'plain_text']]);
    $entity->save();
    $first_seq = $this->multiversionManager->lastSequenceId();
    $expected_without_docs['results'][] = [
      'changes' => [['rev' => $entity->_rev->value]],
      'id' => $entity->uuid(),
      'seq' => $first_seq,
    ];
    $expected_with_docs['results'][] = [
      'changes' => [['rev' => $entity->_rev->value]],
      'id' => $entity->uuid(),
      'seq' => $first_seq,
      'doc' => $serializer->normalize($entity)
    ];

    // Create a new entity.
    $entity = $this->entityTypeManager->getStorage('entity_test_rev')->create();
    $entity->save();

    // Update the field_test_text field.
    $entity->set('field_test_text', [['value' => $this->randomString(), 'format' => 'plain_text']]);
    $entity->save();

    // Delete the entity.
    $entity->delete();
    $second_seq = $this->multiversionManager->lastSequenceId();
    $expected_without_docs['results'][] = [
      'changes' => [['rev' => $entity->_rev->value]],
      'id' => $entity->uuid(),
      'seq' => $second_seq,
      'deleted' => true,
    ];
    $expected_with_docs['results'][] = [
      'changes' => [['rev' => $entity->_rev->value]],
      'id' => $entity->uuid(),
      'seq' => $second_seq,
      'doc' => $serializer->normalize($entity),
      'deleted' => true,
    ];

    $expected_with_docs['last_seq'] = $expected_without_docs['last_seq'] = $this->multiversionManager->lastSequenceId();

    $response = $this->httpRequest("$this->dbname/_changes", 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$this->dbname/_changes", 'GET', NULL, $this->defaultMimeType, NULL, ['include_docs' => 'true']);
    $this->assertResponse('200', 'HTTP response code is correct when including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_with_docs, 'The result is correct when including docs.');

    // Test when using 'since' query parameter.
    $response = $this->httpRequest("$this->dbname/_changes", 'GET', NULL, $this->defaultMimeType, NULL, ['since' => 1]);
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$this->dbname/_changes", 'GET', NULL, $this->defaultMimeType, NULL, ['since' => $first_seq]);
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // Unset first value from results, it shouldn't be returned when since == $first_seq.
    unset($expected_without_docs['results'][0]);
    // Reset the keys of the results array.
    $expected_without_docs['results'] = array_values($expected_without_docs['results']);
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    $response = $this->httpRequest("$this->dbname/_changes", 'GET', NULL, $this->defaultMimeType, NULL, ['since' => $second_seq]);
    $this->assertResponse('200', 'HTTP response code is correct when not including docs.');
    $this->assertHeader('content-type', $this->defaultMimeType);

    $data = Json::decode($response);
    // The result array should be empty in this case.
    $expected_without_docs['results'] = [];
    $this->assertEqual($data, $expected_without_docs, 'The result is correct when not including docs.');

    // @todo: {@link https://www.drupal.org/node/2600488 Assert the sort order.}
  }

}
