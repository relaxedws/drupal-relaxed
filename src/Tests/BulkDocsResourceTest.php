<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_bulk_docs resource.
 *
 * @group relaxed
 */
class BulkDocsResourceTest extends ResourceTestBase {

  public function testPostCreate() {
    $this->enableService('relaxed:bulk_docs', 'POST');

    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'administer workspaces';
      $permissions[] = 'restful post relaxed:bulk_docs';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $data = array('docs' => []);
      foreach ($this->createTestEntities($entity_type) as $entity) {
        $data['docs'][] = $this->container->get('replication.normalizer.content_entity')->normalize($entity, $this->defaultFormat);
      }

      $response = $this->httpRequest("$this->dbname/_bulk_docs", 'POST', Json::encode($data));
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
    $this->enableService('relaxed:bulk_docs', 'POST');
    /** @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = $this->container->get('serializer');

    $entity_type = 'entity_test_rev';

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions($entity_type, 'update');
    $permissions[] = 'administer workspaces';
    $permissions[] = 'restful post relaxed:bulk_docs';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // We set this here just to test creation and saving
    // (with 'relaxed:bulk_docs') the entity on the same workspace.
    $this->multiversionManager->setActiveWorkspaceId($this->workspace->id());

    $input = ['docs' => []];
    $entities = $this->createTestEntities($entity_type, TRUE);
    foreach ($entities as $key => $entity) {
      $entity->set(
        'field_test_text',
        [
          0 => [
            'value' => $this->randomString(),
            'format' => 'plain_text',
          ],
        ]
      );
      if ($key == 1) {
        // Delete an entity.
        $entity->delete();
      }
      $input['docs'][] = $this->container->get('replication.normalizer.content_entity')->normalize($entity, $this->defaultFormat);
    }

    $response = $this->httpRequest("$this->dbname/_bulk_docs", 'POST', Json::encode($input));
    $this->assertResponse('201', 'HTTP response code is correct when entities are updated.');
    $output = Json::decode($response);
    $this->assertTrue(is_array($output), 'Data format is correct.');
    foreach ($output as $key => $value) {
      $entity_number = $key+1;
      $this->assertTrue(isset($value['rev']), "POST request returned a revision hash for entity number $entity_number.");
      $this->assertEqual($value['id'], $entities[$key]->uuid->value, "POST request returned correct ID for entity number $entity_number.");
    }

    foreach ($input['docs'] as $key => $value) {
      $entity_number = $key+1;
      $entity = $this->entityRepository->loadEntityByUuid($entity_type, $value['_id']);
      if ($key == 1) {
        $this->assertEqual($entity, NULL, "Entity number $entity_number has been deleted.");
      }
      else {
        $this->assertEqual(
          $entity->get('field_test_text')->value,
          $input['docs'][$key]['en']['field_test_text'][0]['value'],
          "Correct value for 'field_test_text' for entity number $entity_number."
        );
        list($count) = explode('-', $entity->_rev->value);
        $this->assertEqual($count, 2, "Entity number $entity_number has two revisions.");
      }
    }

    $entities = $this->createTestEntities($entity_type, TRUE);
    foreach ($entities as $key => $entity) {
      $patched_entities['docs'][$key] = $this->entityTypeManager->getStorage($entity_type)->load($entity->id());
      $patched_entities['docs'][$key]->set(
        'field_test_text',
        [
          0 => [
            'value' => $this->randomString(),
            'format' => 'plain_text',
          ],
        ]
      );
      if ($key == 1) {
        // Delete an entity.
        $patched_entities['docs'][$key]->delete();
      }
    }

    $patched_entities['new_edits'] = FALSE;
    $serialized = $serializer->serialize($patched_entities, $this->defaultFormat);
    $response = $this->httpRequest("$this->dbname/_bulk_docs", 'POST', $serialized);
    $this->assertResponse('201', 'HTTP response code is correct when entities are updated.');
    $data = Json::decode($response);
    $this->assertTrue(is_array($data), 'Data format is correct.');

    foreach ($data as $key => $entity_info) {
      $entity_number = $key+1;
      $this->assertTrue(isset($entity_info['rev']), "POST request returned a revision hash for entity number $entity_number.");
      $new_rev = $entity_info['rev'];
      $old_rev = $patched_entities['docs'][$key]->_rev->value;
      $this->assertEqual($new_rev, $old_rev, "POST request returned unchanged revision ID for entity number $entity_number.");
    }

  }

  /**
   * Creates test entities.
   */
  protected function createTestEntities($entity_type, $save = FALSE, $number = 3) {
    $entities = [];

    while ($number >= 1) {
      $entity = $this->entityTypeManager->getStorage($entity_type)->create();
      if ($save) {
        $entity->save();
      }
      $entities[] = $entity;
      $number--;
    }

    return $entities;
  }

}
