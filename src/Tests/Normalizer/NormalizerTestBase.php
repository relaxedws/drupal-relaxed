<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\Normalizer\NormalizerTestBase.
 */

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\serialization\Tests\NormalizerTestBase as CoreNormalizerTestBase;

abstract class NormalizerTestBase extends CoreNormalizerTestBase {

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('key_value', array('key_value_sorted'));

    $this->serializer = $this->container->get('serializer');

    $this->container
      ->get('entity.definition_update_manager')
      ->applyUpdates();

    $workspace = entity_create('workspace', array('id' => 'default'));
    $workspace->save();
  }

}
