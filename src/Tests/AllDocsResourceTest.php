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
    $permissions[] = 'administer workspaces';
    $permissions[] = 'restful get relaxed:all_docs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // We set this here just to test creation, saving and then getting
    // (with 'relaxed:all_docs') entities on the same workspace.
    $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

    $entities = [];
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entities[0] = $storage->create();
      $entities[0]->save();
      $entities[1] = $storage->create();
      $entities[1]->save();
    }

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple();

    $entities = array_merge($entities, $users);

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
    $expected = [
      'offset' => 0,
      'rows' => $rows,
      'total_rows' => 6,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET');
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    $this->assertEqual($expected['offset'], $data['offset'], "Correct value for offset key when not including docs.");
    $this->assertEqual($expected['total_rows'], $data['total_rows'], "Correct value for total_rows key when not including docs.");
    $this->assertEqual(count($expected['rows']), count($data['rows']), "Correct number of rows when not including docs.");

    foreach (array_keys($data['rows']) as $key) {
      $this->assertEqual($expected['rows'][$key], $data['rows'][$key], "Correct value for $key key when not including docs.");
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
    $expected = [
      'total_rows' => 6,
      'offset' => 0,
      'rows' => $rows,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET', NULL, NULL, NULL, ['include_docs' => 'true']);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    $this->assertEqual($expected['offset'], $data['offset'], "Correct value for offset key when including docs.");
    $this->assertEqual($expected['total_rows'], $data['total_rows'], "Correct value for total_rows key when including docs.");
    $this->assertEqual(count($expected['rows']), count($data['rows']), "Correct number of rows when including docs.");

    foreach (array_keys($data['rows']) as $key) {
      $this->assertEqual($expected['rows'][$key], $data['rows'][$key], "Correct value for $key key when including docs.");
    }
  }

}
