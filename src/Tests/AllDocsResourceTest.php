<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\AllDocsResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\user\Entity\User;

/**
 * Tests the /db/_all_docs resource.
 *
 * @group relaxed
 */
class AllDocsResourceTest extends ResourceTestBase {

  public function testGet() {
    $this->enableService('relaxed:all_docs', 'GET');
    $serializer = \Drupal::service('serializer');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:all_docs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entities = [];
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entities[0] = $storage->create();
      $entities[0]->save();
      $entities[1] = $storage->create();
      $entities[1]->save();
    }

    // Test without including docs.
    foreach ($entities as $entity) {
      $rows[] = array(
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => array(
          'rev' => $entity->_rev->value,
        ),
      );
    }
    // Add the info about the new created user.
    $rows[] = array(
      'id' => $account->uuid(),
      'key' => $account->uuid(),
      'value' => array(
        'rev' => $account->_rev->value,
      ),
    );
    $expected = [
      'offset' => 0,
      'rows' => $rows,
      'total_rows' => 3,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET');
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    foreach (array_keys($data) as $key) {
      $this->assertEqual($expected[$key], $data[$key], "Correct value for $key key when not including docs.");
    }

    // Test with including docs.
    $rows = [];
    foreach ($entities as $entity) {
      $rows[] = array(
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => array(
          'rev' => $entity->_rev->value,
          'doc' => $serializer->normalize($entity),
        ),
      );
    }
    // Add the info about the new created user.
    $account = User::load($account->id());
    $rows[] = array(
      'id' => $account->uuid(),
      'key' => $account->uuid(),
      'value' => array(
        'rev' => $account->_rev->value,
        'doc' => $serializer->normalize($account),
      ),
    );
    $expected = [
      'total_rows' => 3,
      'offset' => 0,
      'rows' => $rows,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET', NULL, NULL, NULL, ['include_docs' => 'true']);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    foreach (array_keys($data) as $key) {
      $this->assertEqual($expected[$key], $data[$key], "Correct value for $key key when including docs.");
    }
  }

}
