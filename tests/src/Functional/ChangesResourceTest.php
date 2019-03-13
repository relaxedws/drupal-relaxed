<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Tests the /db/_changes resource.
 *
 * @group relaxed
 */
class ChangesResourceTest extends RelaxedResourceTestBase {

  /**
   * Contains UUID, rev and sequence for all entities created for testing.
   *
   * @var array
   */
  protected $entitiesData;

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'relaxed.changes';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($account);
    $this->workspaceManager->setActiveWorkspace($this->workspace);
    $storage = $this->entityTypeManager->getStorage('entity_test');
    $i = 10;
    $uuids = [];
    while ($i > 0) {
      $entity = $storage->create(['name' => $this->randomString()]);
      $entity->save();
      $last_seq = $this->multiversionManager->lastSequenceId();
      $uuids[] = [
        'seq' => $last_seq,
        'uuid' => $entity->uuid(),
        'rev' => $entity->_rev->value,
      ];
      $i--;
    }
    $this->entitiesData = $uuids;
    $this->drupalLogout();
  }

  public function testPost() {
    $method = 'POST';
    $this->initAuthentication();
    $url = Url::fromRoute("rest.relaxed.changes.$method", ['db' => $this->workspace->getMachineName()]);
    $request_options = $this->getAuthenticationRequestOptions($method);
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    $response = $this->request($method, $url, $request_options);
    $this->assertResourceErrorResponse(
      403,
      "The following permissions are required: 'restful post relaxed:changes' OR 'perform push replication'.",
      $response
    );

    // Authorise with the necessary permissions.
    $this->setUpAuthorization($method);

    // --- Test getting all changes with a POST request. ---

    $expected_response_body = [];
    $expected_response_body['last_seq'] = end($this->entitiesData)['seq'];
    foreach ($this->entitiesData as $data) {
      $expected_response_body['results'][] = [
        'changes' => [['rev' => $data['rev']]],
        'id' => $data['uuid'],
        'seq' => $data['seq'],
      ];
    }

    $response = $this->request($method, $url, $request_options);
    $this->assertResourceResponse(
      200,
      Json::encode($expected_response_body),
      $response,
      [
        'config:rest.resource.relaxed.changes',
        'http_response',
        'workspace:' . $this->workspace->id(),
      ],
      [
        'headers:Accept',
        'headers:Content-Type',
        'headers:If-None-Match',
        'request_format',
        'url',
        'user.permissions',
      ]
    );

    // --- Test getting specific changes with a POST request. ---

    $expected_response_body = [];
    $request_body = [];
    // Get first five changes.
    $first_five_entities_data = array_slice($this->entitiesData, 0, 5);
    $expected_response_body['last_seq'] = end($first_five_entities_data)['seq'];
    foreach ($first_five_entities_data as $data) {
      $expected_response_body['results'][] = [
        'changes' => [['rev' => $data['rev']]],
        'id' => $data['uuid'],
        'seq' => $data['seq'],
      ];
      $request_body['doc_ids'][] = $data['uuid'];
    }

    $request_options[RequestOptions::BODY] = Json::encode($request_body);
    $response = $this->request($method, $url, $request_options);
    $this->assertResourceResponse(
      200,
      Json::encode($expected_response_body),
      $response,
      [
        'config:rest.resource.relaxed.changes',
        'http_response',
        'workspace:' . $this->workspace->id(),
      ],
      [
        'headers:Accept',
        'headers:Content-Type',
        'headers:If-None-Match',
        'request_format',
        'url',
        'user.permissions',
      ]
    );

    // --- Test getting only the last change with a POST request. ---

    $last_entities_data = end($this->entitiesData);
    $expected_response_body['last_seq'] = $last_entities_data['seq'];
    $expected_response_body['results'] = [
      [
        'changes' => [['rev' => $last_entities_data['rev']]],
        'id' => $last_entities_data['uuid'],
        'seq' => $last_entities_data['seq'],
      ],
    ];
    $request_body['doc_ids'] = [$last_entities_data['uuid']];

    $request_options[RequestOptions::BODY] = Json::encode($request_body);
    $response = $this->request($method, $url, $request_options);
    $this->assertResourceResponse(
      200,
      Json::encode($expected_response_body),
      $response,
      [
        'config:rest.resource.relaxed.changes',
        'http_response',
        'workspace:' . $this->workspace->id(),
      ],
      [
        'headers:Accept',
        'headers:Content-Type',
        'headers:If-None-Match',
        'request_format',
        'url',
        'user.permissions',
      ]
    );

    // --- Test getting a change when the uuid doesn't exist. ---

    $request_body['doc_ids'] = ['00001111-1100-0000-0011-111111000000'];
    $expected_response_body['last_seq'] = 0;
    $expected_response_body['results'] = [];

    $request_options[RequestOptions::BODY] = Json::encode($request_body);
    $response = $this->request($method, $url, $request_options);
    $this->assertResourceResponse(
      200,
      Json::encode($expected_response_body),
      $response,
      [
        'config:rest.resource.relaxed.changes',
        'http_response',
        'workspace:' . $this->workspace->id(),
      ],
      [
        'headers:Accept',
        'headers:Content-Type',
        'headers:If-None-Match',
        'request_format',
        'url',
        'user.permissions',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['restful get relaxed:changes']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['restful post relaxed:changes']);
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

}
