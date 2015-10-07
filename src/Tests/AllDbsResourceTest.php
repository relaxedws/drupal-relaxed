<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\AllDbsResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /_all_dbs resource.
 *
 * @group relaxed
 */
class AllDbsResourceTest extends ResourceTestBase {
  public function testGet() {
    $this->enableService('relaxed:all_dbs', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:all_dbs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $workspace_storage = \Drupal::entityManager()->getStorage('workspace');
    $workspaces_entities = $workspace_storage->loadMultiple(NULL);
    $workspaces = array_keys($workspaces_entities);
    for ($i = 0; $i < 3; $i++) {
      $id = $this->randomMachineName();
      $entity = $workspace_storage->create(array('id' => $id));
      $entity->save();
      $workspaces[] = $id;
    }

    $response = $this->httpRequest('_all_dbs', 'GET');
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    sort($data);
    sort($workspaces);
    $this->assertEqual($data, $workspaces, 'All workspaces names received.');
  }

}
