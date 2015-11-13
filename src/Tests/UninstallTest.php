<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\UninstallTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\system\Tests\Module\ModuleTestBase;

/**
 * Tests install/uninstall relaxed module.
 *
 * @group relaxed
 */
class UninstallTest extends ModuleTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  public function setUp() {
    parent::setUp();
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests that relaxed module can be uninstalled properly.
   */
  public function testInstallUninstall() {
    $modules = ['multiversion', 'relaxed'];
    $this->moduleInstaller->install($modules);
    $this->assertModules($modules, TRUE);
    $this->assertModuleConfig('relaxed');
    $relaxed_config = \Drupal::config('relaxed.settings')->get('resources');
    $rest_config = \Drupal::config('rest.settings')->get('resources');
    foreach ($relaxed_config as $key => $item) {
      if (isset($rest_config[$key])) {
        $this->pass("Relaxed module configuration ($key) found in Rest module configuration.");
      }
    }
    // Only uninstall Relaxed.
    $this->moduleInstaller->uninstall(['relaxed']);
    $rest_config = \Drupal::config('rest.settings')->get('resources');
    $this->assertModules(['relaxed'], FALSE);
    $this->assertNoModuleConfig('relaxed');
    foreach ($relaxed_config as $key => $item) {
      if (isset($rest_config[$key])) {
        $this->fail("Relaxed module configuration ($key) found in Rest module configuration.");
      }
    }
  }

}
