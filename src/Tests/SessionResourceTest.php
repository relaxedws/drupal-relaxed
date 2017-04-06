<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /_session resource.
 *
 * @group relaxed
 */
class SessionResourceTest extends ResourceTestBase {

  public function testGet() {
    $this->enableService('relaxed:session', 'GET');

    // Create a user with the correct permissions and admin role.
    $permissions = ['restful get relaxed:session', 'administer permissions', 'administer users'];
    $account = $this->drupalCreateUser($permissions, 'test_admin_user');
    $roles = $account->getRoles();
    $this->drupalLogin($account);
    \Drupal::configFactory()->getEditable('user.settings')->set('admin_role', $roles[1])->save();

    $response = $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);

    $roles = [
      'authenticated',
      $roles[1],
      '_admin',
    ];
    $expected = [
      'info' => [],
      'ok' => TRUE,
      'userCtx' => [
        'user' => 'test_admin_user',
        'roles' => $roles,
      ],
    ];
    $this->assertIdentical($expected, $data, ('Correct values in response.'));

    // Logout the test_admin_user user.
    $this->drupalLogout();

    // Create a simple user with the correct permissions (no admin role).
    $permissions = ['restful get relaxed:session'];
    $account = $this->drupalCreateUser($permissions, 'test_user');
    $roles = $account->getRoles();
    $this->drupalLogin($account);

    $response = $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);

    $expected = [
      'info' => [],
      'ok' => TRUE,
      'userCtx' => [
        'user' => 'test_user',
        'roles' => $roles,
      ],
    ];
    $this->assertIdentical($expected, $data, ('Correct values in response.'));

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

}
