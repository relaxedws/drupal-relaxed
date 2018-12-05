<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_revs_diff resource.
 *
 * @group relaxed
 */
class RevsDiffResourceTest extends ResourceTestBase {

  public function testPostNoMissingRevisions() {
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'perform push replication';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // We set this here just to test creation and saving (with 'revs_diff')
      // the entity on the same workspace. Set this after the user has been
      // created and logged in.
      \Drupal::service('workspaces.manager')->setActiveWorkspace($this->workspace);

      // Create a new test entity.
      $entity = $this->entityTypeManager->getStorage($entity_type)->useWorkspace($this->workspace->id())->create();
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

      $entity = $this->entityTypeManager->getStorage($entity_type)->useWorkspace($this->workspace->id())->load($entity->id());
      $data = [];
      $uuid = $entity->uuid();
      $revs = $this->revTree->getDefaultBranch($uuid);
      foreach ($revs as $rev => $status) {
        $data[$uuid][] = $rev;
      }

      $response = $this->httpRequest($this->dbname . '/_revs_diff', 'POST', Json::encode($data));
      $this->assertEquals('200', $response->getStatusCode(), 'HTTP response code is correct.');
      $this->assertEquals($response->getBody(), '{}', 'Data format is correct.');
    }
  }

  public function testPostMissingRevisions() {
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'perform push replication';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      // Create a new test entity.
      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
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

      $data =[];
      $id = $entity->uuid();
      $revs = $this->revTree->getDefaultBranch($id);
      foreach ($revs as $rev => $status) {
        $data[$id][] = $rev;
      }

      // Add invalid revision to test missing
      // revisions for the first entity.
      $data[$id] = $missing_keys[$id] = [
        '11-1214293f06b11ea6da4c9da0591111zz'
      ];

      // Create a second new test entity.
      $entity = $this->entityTypeManager->getStorage($entity_type)->useWorkspace($this->workspace->id())->create();
      $entity->save();
      $id = $entity->uuid();

      // Add invalid revisions to test missing
      // revisions for the second entity.
      $data[$id] = $missing_keys[$id] = [
        '22-1214293f06b11ea6da4c9da0592222zz',
        '33-1214293f06b11ea6da4c9da0593333zz',
        '44-1214293f06b11ea6da4c9da0594444zz',
      ];
      $response = $this->httpRequest($this->dbname . '/_revs_diff', 'POST', Json::encode($data));
      $this->assertEquals('200', $response->getStatusCode(), 'HTTP response code is correct.');
      $response_data = Json::decode($response->getBody());
      $this->assertTrue(
        is_array($response_data) && !empty($response_data),
        'Data format is correct, the array is not empty.'
      );
      $correct_response_data = TRUE;
      foreach ($missing_keys as $entity_uuid => $revision_ids) {
        foreach ($revision_ids as $key => $revision_id) {
          if (!isset($response_data[$entity_uuid]['missing'][$key])
            || $revision_ids[$key] != $response_data[$entity_uuid]['missing'][$key]) {
            $correct_response_data = FALSE;
          }
        }
      }
      $this->assertTrue($correct_response_data, 'Correct values in response.');
    }
  }

}