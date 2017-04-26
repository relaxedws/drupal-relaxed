<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    $form['api_root'] = [
      '#type' => 'textfield',
      '#title' => t('API root'),
      '#description' => t('Relaxed API root path, in the format "/relaxed".'),
      '#default_value' => $config->get('api_root'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['set_custom_url'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('set_custom_url'),
      '#title' => $this->t('Set custom URL'),
    ];

    $form['custom_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Custom URL'),
      '#default_value' => $config->get('custom_url'),
      '#description' => t('This URL will be used instead of the base site URL. Don\'t add the api_root to the URL. Example: https://www.example.com.'),
      '#states' => [
        'visible' => [
          ':input[name="set_custom_url"]' => ['checked' => TRUE],
        ],
      ],
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
    
    if ($form_state->getValue('set_custom_url') && $form_state->getValue('custom_url') == '') {
      $form_state->setErrorByName('custom_url', 'Custom URL field can\'t be empty when Set custom URL is true.');
    }

    $config = $this->config('relaxed.settings');
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');
    if ($username && !($password || $config->get('password'))) {
      $form_state->setErrorByName('password', 'When setting a username you must set a password.');
    }
    if (!$username && $password) {
      $form_state->setErrorByName('username', 'When setting a password you must set a username.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('relaxed.settings');
    $password = $form_state->getValue('password') ?: base64_decode($config->get('password'));

    $config
      ->set('api_root', $form_state->getValue('api_root'))
      ->set('set_custom_url', $form_state->getValue('set_custom_url'))
      ->set('custom_url', $form_state->getValue('custom_url'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', base64_encode($password))
      ->save();
  }

}
