<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\EndpointConfigurationTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Endpoint configuration by adding, editing, and deleting an Endpoint.
 *
 * @group relaxed
 */
class EndpointConfigurationTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'file',
    'entity_test',
    'multiversion',
    'rest',
    'relaxed',
    'relaxed_test'
  );

  protected $strictConfigSchema = FALSE;

  /**
   * Tests configuration of Endpoints through administration interface.
   */
  function testEndpointConfiguration() {
    // Create a user with permission to view the Endpoint administration pages.
    $user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($user);

    // Make a POST request to admin/config/services/relaxed.
    $edit = array();
    $edit['endpoint'] = Crypt::hashBase64('workspace:default');
    $this->drupalPostForm('admin/config/services/relaxed', $edit, t('Setup'));
    $this->assertResponse(200);

    // Make a POST request to the individual Endpoint configuration page.
    $edit = array();
    $label = $this->randomMachineName();
    $edit['label'] = $label;
    $edit['id'] = strtolower($label);
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm('admin/config/services/relaxed/add/' . Crypt::hashBase64('workspace:default'), $edit, t('Save'));
    $this->assertResponse(200);

    $this->assertText($label, "Make sure the label appears on the configuration page after we've saved the Endpoint.");

    // Make another POST request to the Endpoint edit page.
    $this->clickLink(t('Edit'));
    preg_match('|admin/config/services/relaxed/(.+)|', $this->getUrl(), $matches);
    $aid = $matches[1];
    $edit = array();
    $new_label = $this->randomMachineName();
    $edit['label'] = $new_label;
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    // Make sure that the Endpoint updated properly.
    $this->assertNoText($label, "Make sure the old Endpoint label does NOT appear on the configuration page after we've updated the complex Endpoint.");
    $this->assertText($new_label, "Make sure the Endpoint label appears on the configuration page after we've updated the complex Endpoint.");

    $this->clickLink(t('Edit'));

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/services/relaxed');
    $this->clickLink(t('Delete'));
    $this->assertResponse(200);
    $edit = array();
    $this->drupalPostForm("admin/config/services/relaxed/$aid/delete", $edit, t('Delete'));
    $this->assertResponse(200);

    // Make sure that the Endpoint was actually deleted.
    $this->drupalGet('admin/config/services/relaxed');
    $this->assertResponse(200);
    $this->assertNoText($new_label, "Make sure the Endpoint label does not appear on the overview page after we've deleted the Endpoint.");

    $endpoint = entity_load('endpoint', $aid);
    $this->assertFalse($endpoint, 'Make sure the Endpoint is gone after being deleted.');
  }
}
