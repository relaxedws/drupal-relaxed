<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Remote configuration by adding, editing, and deleting an Remote.
 *
 * @group relaxed
 * @dependencies workspaces
 */
class RemoteConfigurationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'workspaces',
    'multiversion',
    'relaxed',
    'relaxed_test'
  ];

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
    $this->drupalPostForm('admin/config/workflow/relaxed/', $edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/workflow/remotes/add');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('You have to install the Workspace module prior to setting up new workspaces.');

    // Make a POST request to the individual Remote configuration page.
    $edit = [];
    $label = $this->randomMachineName();
    $edit['label'] = $label;
    $edit['id'] = strtolower($label);
    $edit['uri'] = 'http://example.com/relaxed';
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm('admin/config/workflow/remotes/add/', $edit, t('Save'));
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains($label, "Make sure the label appears on the configuration page after we've saved the Remote.");

    // Make another POST request to the Remote edit page.
    $this->clickLink(t('Edit'));
    preg_match('|admin/config/workflow/remotes/(.+)/edit|', $this->getUrl(), $matches);
    $aid = $matches[1];
    $edit = [];
    $new_label = $this->randomMachineName();
    $edit['label'] = $new_label;
    $edit['username'] = 'user';
    $edit['password'] = 'pass';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Make sure that the Remote updated properly.
    $session->pageTextNotContains($label, "Make sure the old Remote label does NOT appear on the configuration page after we've updated the Remote.");
    $session->pageTextContains($new_label, "Make sure the Remote label appears on the configuration page after we've updated the Remote.");

    $this->clickLink(t('Edit'));

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/workflow/remotes');
    $this->clickLink(t('Delete'));
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $this->drupalPostForm("admin/config/workflow/remotes/$aid/delete", $edit, t('Delete'));
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the Remote was actually deleted.
    $this->drupalGet('admin/config/workflow/remotes');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextNotContains($new_label, "Make sure the Remote label does not appear on the overview page after we've deleted the Remote.");

    $remote = \Drupal::entityTypeManager()->getStorage('remote')->load($aid);
    $this->assertFalse($remote, 'Make sure the Remote is gone after being deleted.');
  }

}
