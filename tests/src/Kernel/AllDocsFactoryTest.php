<?php

namespace Drupal\Tests\relaxed\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workspaces\Entity\Workspace;
use Drupal\relaxed\AllDocs\AllDocsInterface;

/**
 * Tests the AllDocsFactory
 *
 * @group relaxed
 */
class AllDocsFactoryTest extends KernelTestBase {

  public static $modules = [
    'node',
    'serialization',
    'system',
    'user',
    'key_value',
    'workspaces',
    'multiversion',
    'relaxed',
  ];

  /** @var  Workspace */
  protected $workspace;

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('key_value', ['key_value_sorted']);
    $this->installConfig(['multiversion']);
    \Drupal::service('multiversion.manager')->enableEntityTypes();

    $this->workspace = Workspace::create(['machine_name' => 'default', 'type' => 'basic']);
    $this->workspace->save();
  }

  public function testAllDocsFactory() {
    $all_docs = \Drupal::service('replication.alldocs_factory')->get($this->workspace);
    $this->assertTrue(($all_docs instanceof AllDocsInterface));
  }

}
