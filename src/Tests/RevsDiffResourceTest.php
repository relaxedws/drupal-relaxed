<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\relaxed\RevisionDiff\RevisionDiff;

/**
 * Tests the /db/_revs_diff resource.
 *
 * @group relaxed
 */
class RevsDiffResourceTest extends ResourceTestBase {

  public function testPostNoMissingRevisions() {
    $this->enableService('relaxed:revs_diff', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a new test entity.
      $entity = entity_create($entity_type);
      $entity->save();

      // Update the field_test_text field.
      $entity->set(
        'field_test_text',
        array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      // Update the name filed.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(12),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      // Update the name filed again.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(25),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      $data = array();
      $revs_count = $entity->_revs_info->count();
      if ($revs_count > 0) {
        $rev_number = 1;
        $id = $entity->uuid();
        while ($rev_number <= $revs_count) {
          if ($rev = $entity->_revs_info->get($rev_number)->rev) {
            $data[$id][] = $rev;
          }
          $rev_number++;
        }
      }

      $revs_diff = \Drupal::service('relaxed.revs_diff');
      $revs_diff->setEntityKeys($data);
      $serialized = $serializer->serialize($revs_diff, $this->defaultFormat);

      $response = $this->httpRequest(
        $this->workspace->name() . '/_revs_diff', 'POST', $serialized
      );
      $this->assertResponse('200', 'HTTP response code is correct.');
      $data = Json::decode($response);
      $this->assertTrue(empty($data), 'Data format is correct.');
    }
  }

  public function testPostMissingRevisions() {
    $db = $this->workspace->name();
    $this->enableService("relaxed:revs_diff:$db", 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {

      // Create a new test entity.
      $entity = entity_create($entity_type);
      $entity->save();

      // Update the field_test_text field.
      $entity->set(
        'field_test_text',
        array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      // Update the name filed.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(12),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      // Update the name filed again.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(25),
            'format' => 'plain_text',
          ),
        )
      );
      $entity->save();

      $data = array();
      $id = $entity->uuid();
      $revs_count = $entity->_revs_info->count();
      if ($revs_count > 0) {
        $rev_number = 1;
        while ($rev_number <= $revs_count) {
          if ($rev = $entity->_revs_info->get($rev_number)->rev) {
            $data[$id][] = $rev;
          }
          $rev_number++;
        }
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

      $revs_diff = \Drupal::service('relaxed.revs_diff');
      $revs_diff->setEntityKeys($data);
      $serialized = $serializer->serialize($revs_diff, $this->defaultFormat);

      $response = $this->httpRequest(
        $this->workspace->name() . '/_revs_diff', 'POST', $serialized
      );
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
