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

    $form['api_root'] = array(
      '#type' => 'textfield',
      '#title' => t('API root'),
      '#description' => t('Relaxed API root path, in the format "/relaxed".'),
      '#default_value' => $config->get('api_root'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['creds'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default replicator credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['creds']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('username'),
      '#default_value' => $config->get('username'),
      '#size' => 60,
      '#maxlength' => 128,
    );
    $form['creds']['password'] = array(
      '#type' => 'password',
      '#title' => t('password'),
      '#size' => 60,
      '#maxlength' => 128,
    );

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
      ->set('username', $form_state->getValue('username'))
      ->set('password', base64_encode($password))
      ->save();
  }

}
