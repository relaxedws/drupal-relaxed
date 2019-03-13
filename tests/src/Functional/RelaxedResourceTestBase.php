<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;

/**
 * Base class for relaxed resources functional tests.
 */
abstract class RelaxedResourceTestBase extends ResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'file',
    'multiversion',
    'rest',
    'relaxed',
    'relaxed_test',
    'replication',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  protected $multiversionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a new workspace that will be used for testing.
    $name = $this->randomMachineName();
    $this->workspace = $this->createWorkspace($name);
    $this->workspace->save();

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->workspaceManager = $this->container->get('workspace.manager');
    $this->multiversionManager = $this->container->get('multiversion.manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {}

  /**
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($name) {
    return Workspace::create(['machine_name' => $name, 'label' => ucfirst($name), 'type' => 'basic']);
  }

}
