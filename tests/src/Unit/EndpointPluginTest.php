<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\EndpointPluginTest;
 */

namespace Drupal\Tests\relaxed\Unit;

use Drupal\KernelTests\KernelTestBase;
use Doctrine\CouchDB\CouchDBClient;
use Drupal\multiversion\Entity\Workspace;
use Drupal\relaxed\Entity\Endpoint;


/**
 * @group relaxed
 */
class EndpointPluginTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['serialization', 'system', 'rest', 'key_value', 'multiversion', 'relaxed'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['multiversion', 'relaxed']);
    $this->installEntitySchema('workspace');
    // Create the default workspace because the multiversion_install() hook is
    // not executed in unit tests.
    Workspace::create(['machine_name' => 'default'])->save();
  }

  /**
   * Test the workspace plugin
   */
  public function testWorkspaceEndpoint() {
    $endpoint = Endpoint::create([
      'id' => 'workspace_default',
      'label' => 'Workspace Default',
      'plugin' => 'workspace:default',
      'configuration' => ['username' => 'foo', 'password' => base64_encode('bar')]
    ]);

    $plugin = $endpoint->getPlugin();
    $this->assertEquals($plugin->getScheme(), 'http');
    $this->assertEquals($plugin->getAuthority(), 'foo:bar@localhost:80');
    $this->assertEquals($plugin->getUserInfo(), 'foo:bar');
    $this->assertEquals($plugin->getHost(), 'localhost');
    $this->assertEquals($plugin->getPort(), 80);
    $this->assertEquals($plugin->getPath(), '/relaxed/default');
    $this->assertEquals($plugin->getQuery(), NULL);
    $this->assertEquals($plugin->getFragment(), NULL);
    $this->assertEquals($plugin->withScheme('https')->getScheme(), 'https');
    $this->assertEquals($plugin->withUserInfo('bar', 'baz')->getUserInfo(), 'bar:baz');
    $this->assertEquals($plugin->withHost('drupal.dev')->getHost(), 'drupal.dev');
    $this->assertEquals($plugin->withPort(8080)->getPort(), 8080);
    $this->assertEquals($plugin->withQuery('foo')->getQuery(), 'foo');
    $this->assertEquals($plugin->withFragment('foo')->getFragment(), 'foo');

    $this->assertTrue(filter_var($plugin, FILTER_VALIDATE_URL), "Plugin returns valid URL");

  }

  /**
   * Test the External plugin
   */
  public function testExternalEndpoint() {
    $endpoint = Endpoint::create([
      'id' => 'external_endpoint',
      'label' => 'External Endpoint',
      'plugin' => 'external',
      'configuration' => [
        'url' => 'http://localhost:5984/target',
        'username' => 'foo',
        'password' => base64_encode('bar')
      ]
    ]);

    $plugin = $endpoint->getPlugin();
    $this->assertEquals($plugin->getScheme(), 'http');
    $this->assertEquals($plugin->getAuthority(), 'foo:bar@localhost:5984');
    $this->assertEquals($plugin->getUserInfo(), 'foo:bar');
    $this->assertEquals($plugin->getHost(), 'localhost');
    $this->assertEquals($plugin->getPort(), 5984);
    $this->assertEquals($plugin->getPath(), '/target');
    $this->assertEquals($plugin->getQuery(), NULL);
    $this->assertEquals($plugin->getFragment(), NULL);
    $this->assertEquals($plugin->withScheme('https')->getScheme(), 'https');
    $this->assertEquals($plugin->withUserInfo('bar', 'baz')->getUserInfo(), 'bar:baz');
    $this->assertEquals($plugin->withHost('couchdb.local')->getHost(), 'couchdb.local');
    $this->assertEquals($plugin->withPort(5985)->getPort(), 5985);
    $this->assertEquals($plugin->withQuery('foo')->getQuery(), 'foo');
    $this->assertEquals($plugin->withFragment('foo')->getFragment(), 'foo');

    $this->assertTrue(filter_var($plugin, FILTER_VALIDATE_URL), "Plugin returns valid URL");

  }

}
