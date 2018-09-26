<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Tests the relaxed:root resource.
 *
 * @group relaxed
 */
class RootResourceTest extends ResourceTestBase {

  public function testGet() {
    // Create a user with the correct permissions.
    $permissions[] = 'perform pull replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest('', 'GET');
    $this->assertSame($response->getStatusCode(), 200, 'HTTP response code is correct.');
    $this->assertSame($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode($response->getBody());

    $request = \Drupal::request();
    $uuid = MD5($request->getHost() . $request->getPort());
    $expected = [
      'couchdb' => 'Welcome',
      'uuid' => $uuid,
      'vendor' => [
        'name' => 'Drupal',
        'version' => \Drupal::VERSION,
      ],
      'version' => \Drupal::VERSION,
    ];
    $this->assertSame($expected, $data, ('Correct values in response.'));
  }

}
