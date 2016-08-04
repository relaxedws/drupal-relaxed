<?php

namespace Drupal\relaxed\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Remote configuration by adding, editing, and deleting an Remote.
 *
 * @group relaxed
 * @dependencies workspace
 */
class RemoteConfigurationTest extends WebTestBase {

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
   * Tests configuration of Remotes through administration interface.
   */
  function testRemoteConfiguration() {
    // Create a user with permission to view the Remote administration pages.
    $user = $this->drupalCreateUser(['administer site configuration', 'administer workspaces', 'access administration pages']);
    $this->drupalLogin($user);

    // Set default replicator credentials
    $edit = [];
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm('admin/config/relaxed/settings/', $edit, t('Save configuration'));
    $this->assertResponse(200);

    if (!\Drupal::moduleHandler()->moduleExists('workspace')) {
      $this->drupalGet('admin/config/services/relaxed/add');
      $this->assertResponse(200);
      $this->assertText('You have to install the Workspace module prior to setting up new workspaces.');
      \Drupal::service('module_installer')->install(['workspace']);
    }

    $this->drupalGet('admin/config/services/relaxed/add');
    $this->assertResponse(200);
    $this->assertNoText('You have to install the Workspace module prior to setting up new workspaces.');

    // Make a POST request to the individual Remote configuration page.
    $edit = array();
    $label = $this->randomMachineName();
    $edit['label'] = $label;
    $edit['id'] = strtolower($label);
    $edit['uri'] = 'http://example.com/relaxed';
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm('admin/config/services/relaxed/add/', $edit, t('Save'));
    $this->assertResponse(200);

    $this->assertText($label, "Make sure the label appears on the configuration page after we've saved the Remote.");

    // Make another POST request to the Remote edit page.
    $this->clickLink(t('Edit'));
    preg_match('|admin/config/services/relaxed/(.+)/edit|', $this->getUrl(), $matches);
    $aid = $matches[1];
    $edit = array();
    $new_label = $this->randomMachineName();
    $edit['label'] = $new_label;
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    // Make sure that the Remote updated properly.
    $this->assertNoText($label, "Make sure the old Remote label does NOT appear on the configuration page after we've updated the Remote.");
    $this->assertText($new_label, "Make sure the Remote label appears on the configuration page after we've updated the Remote.");

    $this->clickLink(t('Edit'));

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/services/relaxed');
    $this->clickLink(t('Delete'));
    $this->assertResponse(200);
    $edit = array();
    $this->drupalPostForm("admin/config/services/relaxed/$aid/delete", $edit, t('Delete'));
    $this->assertResponse(200);

    // Make sure that the Remote was actually deleted.
    $this->drupalGet('admin/config/services/relaxed');
    $this->assertResponse(200);
    $this->assertNoText($new_label, "Make sure the Remote label does not appear on the overview page after we've deleted the Remote.");

    $remote = entity_load('remote', $aid);
    $this->assertFalse($remote, 'Make sure the Remote is gone after being deleted.');
  }
}
