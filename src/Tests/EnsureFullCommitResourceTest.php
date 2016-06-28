<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_ensure_full_commit resource.
 *
 * @group relaxed
 */
class EnsureFullCommitResourceTest extends ResourceTestBase {

  public function testPost() {
    $this->enableService('relaxed:ensure_full_commit', 'POST');

    // Create a user with the correct permissions.
    $permissions[] = 'restful post relaxed:ensure_full_commit';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest("$this->dbname/_ensure_full_commit", 'POST', NULL);
    $this->assertResponse('201', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    $expected = [
      'ok' => TRUE,
      'instance_start_time' => (string) $this->workspace->getStartTime(),
    ];
    $this->assertIdentical($expected, $data, ('Correct values in response.'));

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest("$this->dbname/_ensure_full_commit", 'POST', NULL);
    $this->assertResponse('403', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 201.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest("$this->dbname/_ensure_full_commit", 'POST', NULL);
    $this->assertResponse('201', 'HTTP response code is correct.');
  }

}
