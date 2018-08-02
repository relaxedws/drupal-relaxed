<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the /_all_dbs resource.
 *
 * @group relaxed
 */
class AllDbsResourceTest extends ResourceTestBase {

  public function testGet() {
    // Create a user with the correct permissions.
    $permissions[] = 'perform pull replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $workspaces = [];
    /** @var \Drupal\workspaces\WorkspaceInterface $workspace */
    foreach (Workspace::loadMultiple() as $workspace) {
      $workspaces[] = $workspace->id();
    }

    for ($i = 0; $i < 3; $i++) {
      $machine_name = $this->randomMachineName();
      $entity = Workspace::create(['id' => $machine_name, 'label' => $machine_name]);
      $entity->save();
      $workspaces[] = $machine_name;
    }

    $response = $this->httpRequest('_all_dbs', 'GET');
    $this->assertEquals('200', $response->getStatusCode());
    $this->assertEquals($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode($response->getBody());
    sort($data);
    sort($workspaces);
    $this->assertEquals($workspaces, $data, 'All workspaces names received.');
  }

}
