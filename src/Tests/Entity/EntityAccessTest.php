<?php

namespace Drupal\relaxed\Tests\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity access.
 *
 * @group Entity
 */
class EntityAccessTest extends WebTestBase  {

  public static $modules = array(
    'entity_test',
    'file',
    'multiversion',
    'rest',
    'relaxed',
    'relaxed_test'
  );

  protected $strictConfigSchema = FALSE;

  /**
   * Asserts entity access correctly grants or denies access.
   */
  function assertEntityAccess($ops, AccessibleInterface $object, AccountInterface $account = NULL) {
    foreach ($ops as $op => $result) {
      $message = format_string("Entity access returns @result with operation '@op'.", array(
        '@result' => !isset($result) ? 'null' : ($result ? 'true' : 'false'),
        '@op' => $op,
      ));

      $this->assertEqual($result, $object->access($op, $account), $message);
    }
  }

  /**
   * Ensures entity and field access is properly working.
   */
  function testEntityAndFieldAccess() {
    // Test entity access with 'perform pull replication' permission.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $values = [
      'name' => $this->randomMachineName(),
      'user_id' => 1,
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
    ];
    $entity = EntityTest::create($values);

    // The current user is allowed to view entities.
    $this->assertEntityAccess(array(
      'create' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'view' => TRUE,
    ), $entity);

    // Test entity access with 'perform push replication' permission.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);

    // The current user is allowed to do all operations.
    $this->assertEntityAccess(array(
      'create' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
      'view' => TRUE,
    ), $entity);

    // The custom user is not allowed to perform any operation on test entities.
    $custom_user = $this->createUser();
    $this->assertEntityAccess(array(
      'create' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'view' => FALSE,
    ), $entity, $custom_user);
  }

}
