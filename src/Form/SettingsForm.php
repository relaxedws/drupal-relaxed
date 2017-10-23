<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 *
 * @package Drupal\relaxed\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'relaxed.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relaxed_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('relaxed.settings');

    $encrypt = \Drupal::service('module_handler')->moduleExists('encrypt');
    if ($encrypt) {
      $encrypt_profiles = \Drupal::service('encrypt.encryption_profile.manager')->getEncryptionProfileNamesAsOptions();
    }

    $form['api_root'] = [
      '#type' => 'textfield',
      '#title' => t('API root'),
      '#description' => t('Relaxed API root path, in the format "/relaxed".'),
      '#default_value' => $config->get('api_root'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['creds'] = [
      '#type' => 'fieldset',
      '#title' => t('Default replicator credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['creds']['username'] = [
      '#type' => 'textfield',
      '#title' => t('username'),
      '#default_value' => $config->get('username'),
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['creds']['password'] = [
      '#type' => 'password',
      '#title' => t('password'),
      '#size' => 60,
      '#maxlength' => 128,
    ];

    $form['encryption'] = [
      '#type' => 'details',
      '#title' => t('Encryption'),
      '#description' => t('Encrypt all sensitive data using the Encrypt module including passwords and URIs.'),
      '#open' => $encrypt,
    ];
    $form['encryption']['encrypt'] = [
      '#type' => 'checkbox',
      '#title' => t('Encrypt all sensitive data'),
      '#default_value' => $config->get('encrypt'),
      '#disabled' => !$encrypt || empty($encrypt_profiles),
    ];
    if (!empty($encrypt_profiles)) {
      $form['encryption']['encrypt_profile'] = [
        '#type' => 'select',
        '#title' => t('Encryption profile'),
        '#options' => $encrypt_profiles,
        '#default_value' => $config->get('encrypt_profile'),
        '#states' => [
          'visible' => [
            ':input[name="encrypt"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="encrypt"]' => ['checked' => TRUE],
          ],
          'disabled' => [
            ':input[name="encrypt"]' => ['checked' => FALSE],
          ],
        ],
        '#required' => FALSE,
      ];
    }
    elseif ($encrypt) {
      $args = [
        '@encryption_profile' => \Drupal::l('encryption profile', Url::fromRoute('entity.encryption_profile.collection')),
      ];
      $form['encryption']['help'] = [
        '#type' => 'item',
        '#description' => t('An @encryption_profile must be created before encryption can be enabled.', $args),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $api_root = $form_state->getValue('api_root');
    if (!preg_match('/\A\/[^\/]*\z/', $api_root)) {
      $form_state->setErrorByName('api_root', 'API root must start, but not end with a slash.');
    }

    $config = $this->config('relaxed.settings');
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');
    $encrypt = $form_state->getValue('encrypt');
    $encrypt_profile = $form_state->getValue('encrypt_profile');

    if (!$username && $password) {
      $form_state->setErrorByName('username', 'A username must be entered when setting a password.');
    }
    if ($username && !$password) {
      $form_state->setErrorByName('password', 'A password must be entered when setting a username.');
    }

    if ($encrypt && !$encrypt_profile) {
      $form_state->setErrorByName('encrypt_profile', 'An encryption profile must be selected when enabling encryption.');
    }

    // Check if encryption settings have changed.
    if ($encrypt != $config->get('encrypt') || $encrypt_profile != $config->get('encrypt_profile')) {
      // Force the credentials to be entered if the encryption settings change.
      if (!$username) {
        $form_state->setErrorByName('username', 'Credentials must be entered when changing encryption settings.');
      }
      else {
        // Warn the user when altering encryption settings to update remote
        // settings.
        $args = [
          '@remotes' => \Drupal::l('relaxed remotes', Url::fromRoute('entity.remote.collection')),
        ];
        drupal_set_message(t('All @remotes must be updated when altering the encryption settings.', $args), 'warning');
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Get configuration and update it from form.
    $config = $this->config('relaxed.settings');
    $config
      ->set('api_root', $form_state->getValue('api_root'))
      ->set('username', $form_state->getValue('username'))
      ->set('encrypt', $form_state->getValue('encrypt'))
      ->set('encrypt_profile', $form_state->getValue('encrypt_profile'))
      ->save();

    // If a password is entered then update it.
    $password = $form_state->getValue('password');
    if ($password) {
      $password = \Drupal::service('relaxed.sensitive_data.transformer')->set($password);
      $config->set('password', $password)->save();
    }
  }

}
