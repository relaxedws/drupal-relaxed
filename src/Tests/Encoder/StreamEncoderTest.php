<?php

namespace Drupal\relaxed\Tests\Encoder;

use Drupal\relaxed\Encoder\StreamEncoder;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the stream encoder.
 *
 * @group relaxed
 */
class StreamEncoderTest extends KernelTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\relaxed\Encoder\StreamEncoder
   */
  protected $encoder;

  protected function setUp() {
    parent::setUp();
    $this->encoder = new StreamEncoder();
  }

  public function testEncode() {
    $a_string = 'foo';
    $a = fopen('php://memory', 'w+b');
    fwrite($a, $a_string);
    rewind($a);

    $b_string = 'foo';
    $b = fopen('php://memory', 'w+b');
    fwrite($b, $b_string);
    rewind($b);

    $this->assertEqual($a_string, $this->encoder->encode($a, 'stream'));
    $this->assertEqual(base64_encode($b_string), $this->encoder->encode($b, 'base64_stream'));
  }

  public function testDencode() {
    $a_string = 'foo';
    $a = $this->encoder->decode($a_string, 'stream');

    $b_string = base64_encode('foo');
    $b = $this->encoder->decode($b_string, 'base64_stream');

    $this->assertTrue(is_resource($a));
    $this->assertEqual(stream_get_contents($a), $a_string);

    $this->assertTrue(is_resource($b));
    $this->assertEqual(stream_get_contents($b), base64_decode($b_string));
  }

}
