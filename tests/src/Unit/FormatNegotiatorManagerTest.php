<?php

namespace Drupal\Tests\relaxed\Unit;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\relaxed\Plugin\FormatNegotiator\JsonNegotiator;
use Drupal\relaxed\Plugin\FormatNegotiator\StreamNegotiator;

/**
 * @coversDefaultClass \Drupal\relaxed\Plugin\FormatNegotiatorManager
 * @group relaxed
 */
class FormatNegotiatorManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['serialization', 'system', 'user', 'key_value', 'multiversion', 'relaxed', 'replication'];

  /**
   * @var \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface
   */
  protected $manager;

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

    $this->manager = $this->container->get('plugin.manager.format_negotiator');
  }

  /**
   * @covers ::availableFormats
   */
  public function testAvailableFormats() {
    $expected = ['stream', 'base64_stream', 'json', 'mixed', 'related'];
    $this->assertEquals($expected, $this->manager->availableFormats());
  }

  /**
   * @covers ::select
   *
   * @dataProvider providerTestSelect
   */
  public function testSelect($expected_instance, $params) {
    $this->assertInstanceOf($expected_instance, call_user_func_array([$this->manager, 'select'], $params));
  }

  /**
   * Data provider for testSelect().
   */
  public function providerTestSelect() {
    $data = [];

    $data['json_request'] = [JsonNegotiator::class, ['json', 'post', 'request']];
    $data['json_response'] = [JsonNegotiator::class, ['json', 'post', 'response']];
    $data['mixed_request'] = [JsonNegotiator::class, ['mixed', 'post', 'request']];
    $data['mixed_response'] = [JsonNegotiator::class, ['mixed', 'post', 'response']];
    $data['related_request'] = [JsonNegotiator::class, ['related', 'post', 'request']];
    $data['related_response'] = [JsonNegotiator::class, ['related', 'post', 'response']];

    $data['stream_request'] = [StreamNegotiator::class, ['stream', 'get', 'request']];
    $data['base64_stream_request'] = [StreamNegotiator::class, ['base64_stream', 'get', 'request']];
    // For GET (and HEAD) requests, streaming is allowed in responses.
    $data['stream_response'] = [StreamNegotiator::class, ['stream', 'get', 'response']];
    $data['base64_stream_response'] = [StreamNegotiator::class, ['base64_stream', 'get', 'response']];

    $data['stream_request'] = [StreamNegotiator::class, ['stream', 'post', 'request']];
    $data['base64_stream_request'] = [StreamNegotiator::class, ['base64_stream', 'post', 'request']];
    // Stream does not apply for POST responses, so JSON will be used.
    $data['stream_response'] = [JsonNegotiator::class, ['stream', 'post', 'response']];
    $data['base64_stream_response'] = [JsonNegotiator::class, ['base64_stream', 'post', 'response']];

    return $data;
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    // Test priority weighting of definitions. Stream should be ahead of JSON as
    // it has a higher priority.
    $this->assertEquals(['stream', 'json'], array_keys($this->manager->getDefinitions()));
  }

}
