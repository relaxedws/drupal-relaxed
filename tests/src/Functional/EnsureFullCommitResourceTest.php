<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_ensure_full_commit resource.
 *
 * @group relaxed
 */
class EnsureFullCommitResourceTest extends ResourceTestBase {

  public function testPost() {
    // Create a user with the correct permissions.
    $permissions[] = 'administer workspaces';
    $permissions[] = 'perform push replication';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest("$this->dbname/_ensure_full_commit", 'POST');
    $this->assertEquals('201', $response->getStatusCode());
    $this->assertEquals($this->defaultMimeType, $response->getHeader('content-type')[0]);
    $data = Json::decode($response->getBody());

    $expected = [
      'ok' => TRUE,
      'instance_start_time' => (string) $this->workspace->created->value,
    ];
    $this->assertSame($expected, $data, ('Correct values in response.'));

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest("$this->dbname/_ensure_full_commit", 'POST');
    $this->assertEquals('403', $response->getStatusCode());
  }

}
