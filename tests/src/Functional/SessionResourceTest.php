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
    // Create a user with the correct permissions and admin role.
    $permissions = [
      'administer permissions',
      'administer users',
    ];
    $account = $this->drupalCreateUser($permissions, 'test_admin_user');
    $roles = $account->getRoles();

    \Drupal::entityTypeManager()->getStorage('user_role')
      ->load($roles[1])
      ->setIsAdmin(TRUE)
      ->save();

    $this->drupalLogin($account);

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

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication'], 'test_user_pull');
    $roles = $account->getRoles();

    $roles = [
      'authenticated',
      $roles[1],
    ];

    $expected = [
      'info' => [],
      'ok' => TRUE,
      'userCtx' => [
        'user' => 'test_user_pull',
        'roles' => $roles,
      ],
    ];

    $this->drupalLogin($account);
    $response = $this->httpRequest('_session', 'GET', NULL);
    $data = Json::decode($response);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertIdentical($expected, $data, ('Correct values in response.'));

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication'], 'test_user_push');
    $roles = $account->getRoles();

    $expected = [
      'info' => [],
      'ok' => TRUE,
      'userCtx' => [
        'user' => 'test_user_push',
        'roles' => $roles,
      ],
    ];

    $this->drupalLogin($account);
    $response = $this->httpRequest('_session', 'GET', NULL);
    $data = Json::decode($response);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertIdentical($expected, $data, ('Correct values in response.'));
  }

}
