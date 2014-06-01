<?php

namespace Drupal\couch_api\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\Language;
use Drupal\rest\Tests\RESTTestBase;

/**
 * @todo Test more entity types, at least node, taxonomy term, comment and user.
 */
class DocResourceTest extends RESTTestBase {

  public static $modules = array('rest', 'entity_test', 'couch_api');

  public static function getInfo() {
    return array(
      'name' => '/db/doc',
      'description' => 'Tests the /db/doc resource.',
      'group' => 'Couch API',
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

    $this->repository = entity_create('repository', array('name' => $this->randomName()));
    $this->repository->save();
  }

  public function testHead() {
    $db = $this->repository->name();

    // HEAD and GET is handled by the same resource.
    $this->enableService("couch:root:$db:doc", 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = "restful get couch:root:$db:doc";
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);
  
      $entity = entity_create($entity_type);
      $entity->save();
      $first_rev = $entity->_revs_info->rev;

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'HEAD', NULL, $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      // @todo Change when a proper event handler is implemented for ETag.
      $this->assertHeader('x-couchdb-etag', $first_rev);
      $this->assertTrue(empty($response), 'HEAD request returned no body.');

      $new_name = $this->randomName();
      $entity->name = $new_name;
      $entity->save();
      $second_rev = $entity->_revs_info->rev;

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'HEAD', NULL, $this->defaultMimeType);
      $this->assertHeader('x-couchdb-etag', $second_rev);

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'HEAD', array('rev' => $first_rev), $this->defaultMimeType);
      $this->assertHeader('x-couchdb-etag', $first_rev);
    }
  }

  public function testGet() {
    $db = $this->repository->name();

    $this->enableService("couch:root:$db:doc", 'GET');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'view');
      $permissions[] = "restful get couch:root:$db:doc";
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'GET', NULL, $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct.');
      $this->assertHeader('content-type', $this->defaultMimeType);
      // @todo Change when a proper event handler is implemented for ETag.
      $this->assertHeader('x-couchdb-etag', $entity->_revs_info->rev);
      $data = Json::decode($response);
      // Only assert one example property here, other properties should be
      // checked in serialization tests.
      $this->assertEqual($data['_rev'], $entity->_revs_info->rev, 'GET request returned correct revision hash.');
    }
  }

  public function testPut() {
    $db = $this->repository->name();

    $this->enableService("couch:root:$db:doc", 'PUT');
    $serializer = $this->container->get('serializer');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = "restful put couch:root:$db:doc";
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);
    
      $entity = entity_create($entity_type);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'PUT', $serialized, $this->defaultMimeType);
      $this->assertResponse('201', 'HTTP response code is correct');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'PUT request returned a revision hash.');
    }
  }

  public function testDelete() {
    $db = $this->repository->name();

    $this->enableService("couch:root:$db:doc", 'DELETE');
    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'delete');
      $permissions[] = "restful delete couch:root:$db:doc";
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = entity_create($entity_type);
      $entity->save();

      $response = $this->httpRequest("/couch/$db/" . $entity->uuid(), 'DELETE', NULL, $this->defaultMimeType);
      $this->assertResponse('200', 'HTTP response code is correct for new database');
      $data = Json::decode($response);
      $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

      $entity = entity_load($entity_type, $entity->id());
      $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');
    }
  }
}
