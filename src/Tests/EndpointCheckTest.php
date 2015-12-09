<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\EndpointCheckTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\relaxed\Entity\Endpoint;

/**
 * Tests EndpointCheck functionality.
 *
 * @group relaxed
 */
class EndpointCheckTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'file',
    'entity_test',
    'multiversion',
    'rest',
    'relaxed',
    'relaxed_test'
  );

  protected $strictConfigSchema = FALSE;

  /**
   * Tests the endpoint check appears on the status report page.
   */
  function testCheckAppearsOnStatusReport() {
    Endpoint::create([
      'id' => 'workspace_default',
      'label' => 'Workspace Default',
      'plugin' => 'workspace:default',
      'configuration' => ['username' => 'replicator', 'password' => base64_encode('replicator')]
    ])->save();

    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'access administration pages', 'access site reports'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200);

    $this->assertText('Relaxed Endpoint: Workspace Default', 'Workspace Default found on status page.');
    $this->assertText('Endpoint is reachable.', 'Ping plugin message found.');
  }
}
