<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_all_docs resource.
 *
 * @group relaxed
 */
class AllDocsResourceTest extends ResourceTestBase {

  public function testGet() {
    $serializer = \Drupal::service('relaxed.serializer');

    // Create a user with the correct permissions.
    $permissions[] = 'administer workspaces';
    $permissions[] = 'perform pull replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // We set this here just to test creation, saving and then getting
    // (with 'relaxed:all_docs') entities on the same workspace.
    $this->workspaceManager->setActiveWorkspace($this->workspace);

    $entities = [];
    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type)->useWorkspace($this->workspace->id());
      $entities[0] = $storage->create();
      $entities[0]->save();
      $entities[1] = $storage->create();
      $entities[1]->save();
    }

    // Test without including docs.
    foreach ($entities as $entity) {
      $rows[] = [
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => [
          'rev' => $entity->_rev->value,
        ],
      ];
    }
    usort($rows, function($a, $b) {
      return ($a['id'] > $b['id']) ? +1 : -1;
    });
    $expected = [
      'offset' => 0,
      'rows' => $rows,
      'total_rows' => 2,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET');
    $this->assertEquals('200', $response->getStatusCode(), 'HTTP response code is correct.');
    $this->assertEquals($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode($response->getBody());
    usort($data['rows'], function($a, $b) {
      return ($a['id'] > $b['id']) ? +1 : -1;
    });
    $this->assertEquals($expected['offset'], $data['offset'], "Correct value for offset key when not including docs.");
    $this->assertEquals($expected['total_rows'], $data['total_rows'], "Correct value for total_rows key when not including docs.");
    $this->assertEquals(count($expected['rows']), count($data['rows']), "Correct number of rows when not including docs.");

    foreach (array_keys($data['rows']) as $key) {
      $this->assertEquals($expected['rows'][$key], $data['rows'][$key], "Correct value for $key key when not including docs.");
    }

    // Test with including docs.
    $rows = [];
    foreach ($entities as $entity) {
      $rows[] = [
        'id' => $entity->uuid(),
        'key' => $entity->uuid(),
        'value' => [
          'rev' => $entity->_rev->value,
          'doc' => $serializer->normalize($entity),
        ],
      ];
    }
    usort($rows, function($a, $b) {
      return ($a['id'] > $b['id']) ? +1 : -1;
    });
    $expected = [
      'total_rows' => 2,
      'offset' => 0,
      'rows' => $rows,
    ];

    $response = $this->httpRequest("$this->dbname/_all_docs", 'GET', NULL, NULL, NULL, ['include_docs' => 'true']);
    $this->assertEquals('200', $response->getStatusCode(), 'HTTP response code is correct.');
    $this->assertEquals($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode($response->getBody());
    usort($data['rows'], function($a, $b) {
      return ($a['id'] > $b['id']) ? +1 : -1;
    });
    $this->assertEquals($expected['offset'], $data['offset'], "Correct value for offset key when including docs.");
    $this->assertEquals($expected['total_rows'], $data['total_rows'], "Correct value for total_rows key when including docs.");
    $this->assertEquals(count($expected['rows']), count($data['rows']), "Correct number of rows when including docs.");

    foreach (array_keys($data['rows']) as $key) {
      $this->assertEquals($expected['rows'][$key], $data['rows'][$key], "Correct value for $key key when including docs.");
    }
  }

}
