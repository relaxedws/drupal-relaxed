<?php

namespace Drupal\couch_api\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\Language;
use Drupal\rest\Tests\RESTTestBase;

class DbResourceTest extends RESTTestBase {

  public static $modules = array('rest', 'entity_test', 'couch_api');

  public static function getInfo() {
    return array(
      'name' => '/db',
      'description' => 'Tests the /db resource.',
      'group' => 'Couch API Resources',
    );
  }

  protected function entityPermissions($entity_type, $operation) {
    $return = parent::entityPermissions($entity_type, $operation);

    // Extending with further entity types.
    if (!$return) {
      switch ($entity_type) {
        case 'entity_test_rev':
          switch ($operation) {
            case 'view':
              return array('view test entity');
            case 'create':
            case 'update':
            case 'delete':
              return array('administer entity_test content');
          }
      }
    }
    return $return;
  }

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->defaultAuth = array('cookie');

    // @todo: Remove once multiversion_install() is implemented.
    \Drupal::service('multiversion.manager')
      ->attachRequiredFields('entity_test_rev', 'entity_test_rev');

    $this->entity = entity_create('repository', array('name' => $this->randomName()));
    $this->entity->save();
  }

  public function testHead() {
    // HEAD and GET is handled by the same resource.
    $this->enableService('couch:root:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('repository', 'view');
    $permissions[] = 'restful get couch:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest('/couch/' . $this->entity->name(), 'HEAD', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertTrue(empty($response), 'HEAD request returned no body.');
  }

  public function testGet() {
    $this->enableService('couch:root:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('repository', 'view');
    $permissions[] = 'restful get couch:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest('/couch/' . $this->entity->name(), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    // Only assert one example property here, other properties should be
    // checked in serialization tests.
    $this->assertEqual($data['db_name'], $this->entity->name(), 'GET request returned correct db_name.');
  }

  public function testPut() {
    $this->enableService('couch:root:db', 'PUT');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('repository', 'create');
    $permissions[] = 'restful put couch:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $name = $this->randomName();
    $response = $this->httpRequest('/couch/' . $name, 'PUT', NULL, $this->defaultMimeType);
    $this->assertResponse('201', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'PUT request returned ok.');

    $entity = entity_load_by_uuid('repository', $name);
    $this->assertTrue(!empty($entity), 'The entity being PUT was loaded.');

    $entity = entity_create('repository', array('name' => $this->randomName()));
    $entity->save();

    // Test putting an existing repository.
    $response = $this->httpRequest('/couch/' . $entity->name(), 'PUT', NULL, $this->defaultMimeType);
    $this->assertResponse('412', 'HTTP response code is correct for existing database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['error']), 'PUT request returned error.');
  }

  public function testPost() {
    $this->enableService('couch:root:db', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful post couch:root:db';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);
    
      $entity = entity_create($entity_type);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest('/couch/' . $this->entity->name(), 'POST', $serialized, $this->defaultMimeType);
      $this->assertResponse('201', 'HTTP response code is correct when posting new entity');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'POST request returned a revision hash.');

      $response = $this->httpRequest('/couch/' . $this->entity->name(), 'POST', $serialized, $this->defaultMimeType);
      $this->assertResponse('409', 'HTTP response code is correct when posting conflicting entity');
    }
  }

  public function testDelete() {
    $this->enableService('couch:root:db', 'DELETE');
  
    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('repository', 'delete');
    $permissions[] = 'restful delete couch:root:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $entity = entity_create('repository', array('name' => $this->randomName()));
    $entity->save();

    $response = $this->httpRequest('/couch/' . $entity->name(), 'DELETE', NULL, $this->defaultMimeType);
    $this->assertResponse('200', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $entity = entity_load('repository', $entity->id());
    $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');
  }
}
