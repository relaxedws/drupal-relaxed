<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Entity\Key;
use Drupal\Tests\BrowserTestBase;

/**
 * @group relaxed
 */
class RelaxedEncryptTest extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'key',
    'encrypt',
    'encrypt_test',
    'relaxed',
    'replication',
    'multiversion',
    'key_value',
    'serialization',
    'rest',
    'basic_auth'
  ];

  /**
   * Tests encrypt integration.
   */
  public function testEncryptIntegration() {
    // Create a 256bit testkey.
    $key_256 = Key::create([
      'id' => 'testing_key_256',
      'label' => 'Testing Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '256'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbitmustbesixteenbit'],
    ]);
    $key_256->save();

    // Create an Encrption Profile.
    $encryption_profile = EncryptionProfile::create([
      'id' => 'encryption_profile',
      'label' => 'Encryption profile',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => 'testing_key_256',
    ]);
    $encryption_profile->save();

    // Login as root.
    $this->drupalLogin($this->rootUser);

    // Add a replication username and password, enable encryption and set
    // encryption profile.
    $this->drupalGet('/admin/config/relaxed/settings');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-username', 'replication_user');
    $page->fillField('edit-password', 'replication_password');
    $page->checkField('edit-encrypt');
    $page->selectFieldOption('edit-encrypt-profile', 'encryption_profile');
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = \Drupal::config('relaxed.settings');
    $transformer = \Drupal::service('relaxed.sensitive_data.transformer');
    // Make sure the password is not stored in plain text.
    $this->assertNotEquals('replication_password', $config->get('password'));
    // Make sure the decrypted password matches the one entered.
    $this->assertEquals('replication_password', $transformer->get($config->get('password')));
  }

}
