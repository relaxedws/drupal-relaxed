<?php

namespace Drupal\relaxed\Tests;

use Drupal\rest\Tests\RESTTestBase;

class ResourceTestBase extends RESTTestBase {

  public static $modules = array('rest', 'entity_test', 'relaxed');

  /**
   * @var string
   */
  protected $api_root;

  /**
   * @var \Drupal\multiversion\Entity\RepositoryInterface
   */
  protected $repository;

  public static function getInfo() {
    return array(
        'name' => '/db/doc',
        'description' => 'Tests the /db/doc resource.',
        'group' => 'Relaxed API',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->defaultAuth = array('cookie');
    $this->apiRoot = \Drupal::config('relaxed.settings')->get('api_root');

    // @todo: Remove once multiversion_install() is implemented.
    \Drupal::service('multiversion.manager')
    ->attachRequiredFields('entity_test_rev', 'entity_test_rev');

    $this->repository = entity_create('repository', array('name' => $this->randomName()));
    $this->repository->save();
  }

  protected function httpRequest($url, $method, $body = NULL, $mime_type = NULL) {
    // Prepend the API root configuration to simplifiy for the tests.
    return parent::httpRequest($this->apiRoot . '/' . $url, $method, $body, $this->defaultMimeType);
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
}
