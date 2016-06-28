<?php

namespace Drupal\Tests\relaxed\Unit;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\relaxed\Entity\Remote;
use GuzzleHttp\Psr7\Uri;

/**
 * @group relaxed
 */
class RemoteTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['serialization', 'system', 'user', 'rest', 'key_value', 'multiversion', 'relaxed', 'replication'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['multiversion', 'relaxed']);
    $this->installEntitySchema('workspace');
    // Create the default workspace because the multiversion_install() hook is
    // not executed in unit tests.
    Workspace::create(['machine_name' => 'default', 'type' => 'basic'])->save();
  }

  /**
   * Test the workspace plugin
   */
  public function testRemoteEntity() {
    /** @var Remote $remote */
    $remote = Remote::create([
      'id' => 'production',
      'label' => 'Production',
      'uri' => base64_encode('http://admin:admin@example.com/relaxed/default'),
    ]);

    /** @var Uri $uri */
    $uri = $remote->uri();
    $this->assertEquals('http', $uri->getScheme());
    $this->assertEquals('admin:admin@example.com', $uri->getAuthority());
    $this->assertEquals('admin:admin', $uri->getUserInfo());
    $this->assertEquals('example.com', $uri->getHost());
    $this->assertEquals(NULL, $uri->getPort());
    $this->assertEquals('/relaxed/default', $uri->getPath());
    $this->assertEquals(NULL, $uri->getQuery());
    $this->assertEquals(NULL, $uri->getFragment());
    $this->assertEquals('https', $uri->withScheme('https')->getScheme());
    $this->assertEquals('bar:baz', $uri->withUserInfo('bar', 'baz')->getUserInfo());
    $this->assertEquals('drupal.dev', $uri->withHost('drupal.dev')->getHost());
    $this->assertEquals(8080, $uri->withPort(8080)->getPort());
    $this->assertEquals('foo', $uri->withQuery('foo')->getQuery());
    $this->assertEquals('foo', $uri->withFragment('foo')->getFragment());

    $this->assertTrue(filter_var($uri, FILTER_VALIDATE_URL), "Plugin returns valid URL");

    $this->assertEquals('http://example.com/relaxed/default', $remote->withoutUserInfo());
    $this->assertEquals('admin', $remote->username());
  }

}
