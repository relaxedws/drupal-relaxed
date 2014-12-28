<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_all_docs resource.
 *
 * @group relaxed
 */
class AllDocsResourceTest extends ResourceTestBase {

  public function testGet() {
    $this->enableService('relaxed:all_docs', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:all_docs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entities = array();
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      $entities[0] = entity_create($entity_type);
      $entities[0]->save();
      $entities[1] = entity_create($entity_type);
      $entities[1]->save();

      $rows = array();
      foreach ($entities as $entity) {
        $rows[] = array(
          'id' => $entity->uuid(),
          'key' => $entity->uuid(),
          'value' => array(
            'rev' => $entity->_revs_info->rev,
          ),
        );
      }

      $expected = array(
        'total_rows' => 2,
        'offset' => 0,
        'rows' => $rows,
      );

      $db = $this->workspace->id();
      $response = $this->httpRequest("$db/_all_docs", 'GET');
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      $data = Json::decode($response);

      foreach (array_keys($data) as $key) {
        $this->assertEqual($expected[$key], $data[$key], "Correct value for $key key.");
      }
    }
  }
}
