<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\InstallUninstallTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\system\Tests\Module\ModuleTestBase;

/**
 * Tests install/uninstall relaxed module.
 *
 * @group relaxed
 */
class InstallUninstallTest extends ModuleTestBase {

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * @var array
   */
  protected $installModules = [
    'multiversion',
    'relaxed',
  ];

  /**
   * @var array
   */
  protected $expectedResources = [
    'relaxed:db',
    'relaxed:attachment',
    'relaxed:bulk_docs',
    'relaxed:changes',
    'relaxed:doc',
    'relaxed:revs_diff',
    'relaxed:local:doc',
    'relaxed:root',
    'relaxed:session',
    'relaxed:ensure_full_commit',
    'relaxed:all_dbs',
    'relaxed:all_docs',
  ];

  public function setUp() {
    parent::setUp();
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests that relaxed module can be installed and uninstalled properly.
   */
  public function testInstallUninstall() {
    // Install Multiversion and Relaxed
    $this->moduleInstaller->install($this->installModules);
    $this->assertModules($this->installModules, TRUE);
    $this->assertModuleConfig('relaxed');
    $relaxed_config = \Drupal::config('relaxed.settings')->get('resources');
    $rest_config = \Drupal::config('rest.settings')->get('resources');
    foreach ($relaxed_config as $key => $item) {
      $this->assertTrue(in_array($key, $this->expectedResources), "Expected resource ($key) found.");
      $this->assertTrue(isset($rest_config[$key]), "Relaxed module configuration ($key) found in Rest module configuration.");
    }

    // Only uninstall Relaxed.
    $this->moduleInstaller->uninstall(['relaxed']);
    $this->assertModules(['relaxed'], FALSE);
    $this->assertNoModuleConfig('relaxed');
    $rest_config = \Drupal::config('rest.settings')->get('resources');
    foreach ($this->expectedResources as $resource) {
      $this->assertTrue(!isset($rest_config[$resource]), "Relaxed module resource ($resource) not found in Rest module configuration.");
    }
  }

}
