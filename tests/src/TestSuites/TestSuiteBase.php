<?php

namespace Drupal\Tests\relaxed\TestSuites;

// If relaxed module is in modules/contrib directory.
//require_once __DIR__ . '/../../../../../../core/tests/TestSuites/TestSuiteBase.php';

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
  protected function addTestsBySuiteNamespace($path, $suite_namespace) {
    if ($suite_namespace == 'Integration') {
      $test_path = "$path/$suite_namespace";
      if (is_dir($test_path)) {
        $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\Tests\\relaxed\\$suite_namespace\\", $test_path));
      }
    }
  }

}
