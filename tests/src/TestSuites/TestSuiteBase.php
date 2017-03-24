<?php

namespace Drupal\Tests\relaxed\TestSuites;

//require_once __DIR__ . '/../../../../../core/tests/TestSuites/TestSuiteBase.php';
// For TravisCI.
require_once __DIR__ . '/../../../../../../www/core/tests/TestSuites/TestSuiteBase.php';

use Drupal\simpletest\TestDiscovery;
use Drupal\Tests\TestSuites\TestSuiteBase as CoreTestSuiteBase;

/**
 * Base class for Drupal test suites.
 */
abstract class TestSuiteBase extends CoreTestSuiteBase {

  /**
   * {@inheritdoc}
   */
  protected function addTestsBySuiteNamespace($root, $suite_namespace) {
    foreach ($this->findExtensionDirectories($root) as $extension_name => $dir) {
      if ($extension_name !== 'relaxed' || $suite_namespace !== 'Integration') {
        continue;
      }
      $test_path = "$dir/tests/src/$suite_namespace";
      if (is_dir($test_path)) {
        $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\Tests\\$extension_name\\$suite_namespace\\", $test_path));
      }
    }
  }

}
