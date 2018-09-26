<?php

namespace Drupal\Tests\relaxed\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workspaces\Entity\Workspace;
use Drupal\relaxed\RevisionDiff\RevisionDiffInterface;

/**
 * Tests the revision diff factory.
 *
 * @group relaxed
 */
class RevisionDiffFactoryTest extends KernelTestBase {

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
    $this->installEntitySchema('workspace');
    $this->installEntitySchema('replication_log');
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('key_value', ['key_value_sorted']);
    $this->installConfig(['multiversion']);
    \Drupal::service('multiversion.manager')->enableEntityTypes();

    $this->workspace = Workspace::create(['id' => 'default']);
    $this->workspace->save();
  }

  public function testChangesFactory() {
    $changes = \Drupal::service('relaxed.revisiondiff_factory')->get($this->workspace);
    $this->assertTrue(($changes instanceof RevisionDiffInterface));
  }

}
