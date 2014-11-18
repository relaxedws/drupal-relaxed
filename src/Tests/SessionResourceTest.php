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
    $permissions = array('restful get relaxed:session', 'administer permissions', 'administer users');
    $account = $this->drupalCreateUser($permissions, 'test_user_admin');
    $roles = $account->getRoles();
    $this->drupalLogin($account);
    \Drupal::config('user.settings')->set('admin_role', $roles[0])->save();

    $response = $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);

    $roles = array(
      'authenticated',
      $roles[0],
      '_admin',
    );
    $expected = array(
      'info' => array(),
      'ok' => TRUE,
      'userCtx' => array(
        'user' => 'test_user_admin',
        'roles' => $roles,
      ),
    );
    $this->assertIdentical($expected, $data, ('Correct values in response.'));

    // Logout the test_user user.
    $this->drupalLogout();

    // Create a simple user with the correct permissions (no admin role).
    $permissions = array('restful get relaxed:session');
    $account = $this->drupalCreateUser($permissions, 'test_user');
    $roles = $account->getRoles();
    $this->drupalLogin($account);

    $response = $this->httpRequest('_session', 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);

    $roles = array(
      'authenticated',
      $roles[0],
    );
    $expected = array(
      'info' => array(),
      'ok' => TRUE,
      'userCtx' => array(
        'user' => 'test_user',
        'roles' => $roles,
      ),
    );
    $this->assertIdentical($expected, $data, ('Correct values in response.'));
  }
}
