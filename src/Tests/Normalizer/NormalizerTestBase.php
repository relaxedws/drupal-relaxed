<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\Normalizer\NormalizerTestBase.
 */

namespace Drupal\relaxed\Tests\Normalizer;

use Drupal\serialization\Tests\NormalizerTestBase as CoreNormalizerTestBase;
use Drupal\multiversion\Entity\Workspace;

abstract class NormalizerTestBase extends CoreNormalizerTestBase {

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  protected function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('key_value', array('key_value_sorted'));
    \Drupal::service('multiversion.manager')->enableEntityTypes();

    $this->serializer = $this->container->get('serializer');

    $workspace = Workspace::create(['id' => 'default']);
    $workspace->save();
  }

}
