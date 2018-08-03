<?php

namespace Drupal\Tests\relaxed\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the integration test suite.
 */
class IntegrationTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all integration tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(dirname(dirname(__DIR__)));

    $suite = new static('integration');
    $suite->addTestsBySuiteNamespace($root, 'Integration');

    return $suite;
  }

}
