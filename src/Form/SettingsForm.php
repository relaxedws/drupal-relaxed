<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\relaxed\Form
 */
class SettingsForm extends ConfigFormBase {

  protected $authenticationCollector;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AuthenticationCollectorInterface $collector) {
    parent::__construct($config_factory);

    $this->authenticationCollector = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('authentication_collector')
    );
  }

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

    $authentication_providers = array_keys($this->authenticationCollector->getSortedProviders());
    $authentication_providers = array_combine($authentication_providers, $authentication_providers);

    $form['api_root'] = [
      '#type' => 'textfield',
      '#title' => t('API root'),
      '#description' => t('Relaxed API root path, in the format "/relaxed".'),
      '#default_value' => $config->get('api_root'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['authentication'] = [
      '#title' => $this->t('Authentication providers'),
      '#description' => $this->t('The allowed authentication providers for relaxed routes.'),
      '#type' => 'checkboxes',
      '#options' => $authentication_providers,
      '#default_value' => $config->get('authentication'),
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

    $config
      ->set('api_root', $form_state->getValue('api_root'))
      ->set('authentication', array_keys(array_filter($form_state->getValue('authentication'))))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
