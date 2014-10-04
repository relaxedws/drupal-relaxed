<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/all-docs resource.
 *
 * @group relaxed
 */
class BulkDocsResourceTest extends ResourceTestBase {

  protected function setUp() {
    parent::setUp();
  }

  public function testPostCreate() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:bulk_docs', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful post relaxed:bulk_docs';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entities = array();
      $entities['docs'] = $this->createTestEntities($entity_type);
      $serialized = $serializer->serialize($entities, $this->defaultFormat);

      $response = $this->httpRequest("$db/_bulk_docs", 'POST', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct when entities are created or updated.');
      $data = Json::decode($response);
      $this->assertTrue(is_array($data), 'Data format is correct.');
      foreach ($data as $key => $entity_info) {
        $entity_number = $key+1;
        $this->assertTrue(isset($entity_info['rev']), "POST request returned a revision hash for entity number $entity_number.");
      }
    }
  }

  public function testPostUpdate() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:bulk_docs', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_type = 'entity_test_rev';

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions($entity_type, 'update');
    $permissions[] = 'restful post relaxed:bulk_docs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entities = $this->createTestEntities($entity_type, TRUE);
    $patched_entities = array();
    foreach ($entities as $key => $entity) {
      $patched_entities['docs'][$key] = entity_load($entity_type, $entity->id(), TRUE);
      $patched_entities['docs'][$key]->set(
        'field_test_text',
        array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          )
        )
      );
      if ($key == 1) {
        // Delete an entity.
        $patched_entities['docs'][$key]->delete();
      }
    }

    $serialized = $serializer->serialize($patched_entities, $this->defaultFormat);
    $response = $this->httpRequest("$db/_bulk_docs", 'POST', $serialized);
    $this->assertResponse('201', 'HTTP response code is correct when entities are updated.');
    $data = Json::decode($response);
    $this->assertTrue(is_array($data), 'Data format is correct.');
    foreach ($data as $key => $entity_info) {
      $entity_number = $key+1;
      $this->assertTrue(isset($entity_info['rev']), "POST request returned a revision hash for entity number $entity_number.");
      $this->assertEqual($entity_info['id'], $patched_entities['docs'][$key]->uuid(), "POST request returned correct ID for entity number $entity_number.");
    }

    foreach ($patched_entities['docs'] as $key => $patched_entity) {
      $entity_number = $key+1;
      $entity = entity_load($entity_type, $patched_entity->id(), TRUE);
      if ($key == 1) {
        $this->assertEqual($entity, NULL, "Entity number $entity_number has been deleted.");
      }
      else {
        $this->assertEqual(
          $entity->get('field_test_text')->value,
          $patched_entity->get('field_test_text')->value,
          "Correct value for 'field_test_text' for entity number $entity_number."
        );
        $this->assertEqual($entity->_revs_info->count(), 2, "Entity number $entity_number has two revisions.");
      }
    }

    $entities = $this->createTestEntities($entity_type, TRUE);
    // @todo The below code is copied from above and should probably be in a method
    foreach ($entities as $key => $entity) {
      $patched_entities['docs'][$key] = entity_load($entity_type, $entity->id(), TRUE);
      $patched_entities['docs'][$key]->set(
        'field_test_text',
        array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          )
        )
      );
      if ($key == 1) {
        // Delete an entity.
        $patched_entities['docs'][$key]->delete();
      }
    }

    $patched_entities['new_edits'] = FALSE;
    $serialized = $serializer->serialize($patched_entities, $this->defaultFormat);
    $response = $this->httpRequest("$db/_bulk_docs", 'POST', $serialized);
    $this->assertResponse('201', 'HTTP response code is correct when entities are updated.');
    $data = Json::decode($response);
    $this->assertTrue(is_array($data), 'Data format is correct.');

    foreach ($data as $key => $entity_info) {
      $entity_number = $key+1;
      $this->assertTrue(isset($entity_info['rev']), "POST request returned a revision hash for entity number $entity_number.");
      $new_rev = $entity_info['rev'];
      $old_rev = $patched_entities['docs'][$key]->_revs_info->get(0)->rev;
      $this->assertEqual($new_rev, $old_rev, "POST request returned unchanged revision ID for entity number $entity_number.");
    }

  }

  /**
   * Creates test entities.
   */
  protected function createTestEntities($entity_type, $save = FALSE, $number = 3) {
    $entities = array();

    while ($number >= 1) {
      $entity = entity_create($entity_type);
      if ($save) {
        $entity->save();
      }
      $entities[] = $entity;
      $number--;
    }

    return $entities;
  }
}
