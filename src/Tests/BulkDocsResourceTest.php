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

    // @todo: Add entities to the workspace.
  }

  public function testPost() {
    $db = $this->workspace->name();
    $this->enableService("relaxed:bulk_docs:$db", 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = "restful post relaxed:bulk_docs:$db";
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entities = $this->createTestEntities($entity_type);
      $serialized = $serializer->serialize($entities, $this->defaultFormat);

      $response = $this->httpRequest($this->workspace->name() . '/bulk-docs', 'POST', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct when posting new entities');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'POST request returned a revision hash.');

      $response = $this->httpRequest($this->workspace->name() . '/bulk-docs', 'POST', $serialized);
      $this->assertResponse('409', 'HTTP response code is correct when posting conflicting entity');
    }
  }

  /**
   * Creates test entities.
   */
  protected function createTestEntities($entity_type, $number = 3) {
    $entities = array();

    while ($number >= 1) {
      $entity = entity_create($entity_type);
      $entity->save();
      $entities[] = $entity;
      $number--;
    }

    return $entities;
  }
}