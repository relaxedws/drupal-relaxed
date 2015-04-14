<?php

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\Component\Utility\String;

/**
 * Tests the attachment serialization format.
 *
 * @group relaxed
 */
class AttachmentNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'file', 'relaxed', 'key_value', 'multiversion', 'rest');

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
    $this->fileEntity = entity_create('file', array('uri' => $meta['uri']));
  }

  public function testNormalize() {
    $normalized = $this->serializer->normalize($this->fileEntity);
    $this->assertTrue(is_resource($normalized), 'File entity was normalized to a file resource.');
  }

  public function testSerialize() {
    $serialized = $this->serializer->serialize($this->fileEntity, 'stream');
    $this->assertEqual($serialized, $this->fileContents, 'File entity was serialized to file contents.');
  }

  public function testDenormalize() {
    $denormalized = $this->serializer->denormalize($this->fileHandle, $this->entityClass, 'stream');
    $this->assertTrue($denormalized instanceof $this->entityClass, String::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->fileEntity->getEntityTypeId(), 'Expected entity type found.');
  }

}
