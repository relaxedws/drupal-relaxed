<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\RevsDiffResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_revs_diff resource.
 *
 * @group relaxed
 */
class RevsDiffResourceTest extends ResourceTestBase {

  public function testPostNoMissingRevisions() {
    $this->enableService('relaxed:revs_diff', 'POST');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful post relaxed:revs_diff';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a new test entity.
      $entity = entity_create($entity_type);
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

      $entity = entity_load($entity_type, $entity->id(), TRUE);
      $data = array();
      $uuid = $entity->uuid();
      $revs = $this->revTree->getDefaultBranch($uuid);
      foreach ($revs as $rev => $status) {
        $data[$uuid][] = $rev;
      }

      $response = $this->httpRequest($this->workspace->id() . '/_revs_diff', 'POST', Json::encode($data));
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertEqual($response, '{}', 'Data format is correct.');
    }
  }

  public function testPostMissingRevisions() {
    $this->enableService('relaxed:revs_diff', 'POST');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful post relaxed:revs_diff';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {

      // Create a new test entity.
      $entity = entity_create($entity_type);
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

      $data = array();
      $id = $entity->uuid();
      $revs = $this->revTree->getDefaultBranch($id);
      foreach ($revs as $rev => $status) {
        $data[$id][] = $rev;
      }

      // Add invalid revision to test missing
      // revisions for the first entity.
      $data[$id] = $missing_keys[$id] = array(
        '11-1214293f06b11ea6da4c9da0591111zz'
      );

      // Create a second new test entity.
      $entity = entity_create($entity_type);
      $entity->save();
      $id = $entity->uuid();

      // Add invalid revisions to test missing
      // revisions for the second entity.
      $data[$id] = $missing_keys[$id] = array(
        '22-1214293f06b11ea6da4c9da0592222zz',
        '33-1214293f06b11ea6da4c9da0593333zz',
        '44-1214293f06b11ea6da4c9da0594444zz',
      );
      $response = $this->httpRequest($this->workspace->id() . '/_revs_diff', 'POST', Json::encode($data));
      $this->assertResponse('200', 'HTTP response code is correct.');
      $response_data = Json::decode($response);
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
