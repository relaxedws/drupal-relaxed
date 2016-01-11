<?php

/**
 * @file
 * Contains \Drupal\Tests\relaxed\Unit\Normalizer\AttachmentNormalizerTest.
 */

namespace Drupal\Tests\relaxed\Unit\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\file\Entity\File;

/**
 * Tests the attachment serialization format.
 *
 * @group relaxed
 */
class AttachmentNormalizerTest extends NormalizerTestBase {

  public static $modules = [
    'serialization',
    'system',
    'field',
    'file',
    'relaxed',
    'key_value',
    'multiversion',
    'rest'
  ];

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var string
   */
  protected $fileContents;

  /**
   * @var stream
   */
  protected $fileHandle;

  /**
   * @var string
   */
  protected $entityClass = 'Drupal\file\Entity\File';

  /**
   * @var \Drupal\file\Entity\File
   */
  protected $fileEntity;

  protected function setUp() {
    parent::setUp();

    $this->fileContents = $this->randomString();
    $this->fileHandle = fopen('temporary://' . $this->randomMachineName(), 'w+b');
    fwrite($this->fileHandle, $this->fileContents);
    rewind($this->fileHandle);

    $meta = stream_get_meta_data($this->fileHandle);
    $this->fileEntity = File::create(['uri' => $meta['uri']]);
  }

  public function testNormalizer() {
    // Test normalize.
    $normalized = $this->serializer->normalize($this->fileEntity);
    $this->assertTrue(is_resource($normalized), 'File entity was normalized to a file resource.');

    // Test serialize.
    $serialized = $this->serializer->serialize($this->fileEntity, 'stream');
    $this->assertEquals($serialized, $this->fileContents, 'File entity was serialized to file contents.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($this->fileHandle, $this->entityClass, 'stream');
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($this->fileEntity->getEntityTypeId(), $denormalized->getEntityTypeId(), 'Expected entity type found.');
  }

}
