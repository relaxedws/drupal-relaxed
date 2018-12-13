<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

    $options = [
      'uid' => $this->t('Map by UID'),
      'anonymous' => $this->t('Map to Anonymous'),
      'uid_1' => $this->t('Map to UID 1'),
    ];

    $form['mapping_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Users mapping type'),
      '#default_value' => $config->get('mapping_type'),
      '#options' => $options,
      '#description' => $this->t("Select how users will be mapped when they can't be mapped by username or email."),
    ];

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UID'),
      '#default_value' => $config->get('mapping_type') === 'uid' ? $config->get('uid') : '',
      '#maxlength' => 60,
      '#size' => 30,
      '#states' => [
        'visible' => [
          'select[name="mapping_type"]' => ['value' => 'uid'],
        ],
      ],
    ];

    $form['changes_limit'] = [
      '#type' => 'number',
      '#title' => t('Changes limit'),
      '#default_value' => $config->get('changes_limit'),
      '#description' => $this->t("This is the limit of changes the 
      replicator will GET per request, if the limit is a smaller number than 
      the total changes, then it will do multiple requests to get all the 
      changes. The bigger this number is, the slower will be the request, but at 
      the same time - the smaller is the limit, the higher is the number of 
      requests, so, there should be set an optimal limit, to not impact the 
      performance. Values range 10 - 1000."),
      '#required' => TRUE,
      '#min' => 10,
      '#max' => 1000,
      '#step' => 10,
    ];

    $form['bulk_docs_limit'] = [
      '#type' => 'number',
      '#title' => t('Bulk docs limit'),
      '#default_value' => $config->get('bulk_docs_limit'),
      '#description' => $this->t("This is the limit of entities the 
      replicator will POST per request, if the limit is a smaller number than 
      the total number of entities that have to be transferred to the destination, 
      then it will do multiple requests to transfer all the entities. The bigger 
      this number is, the slower will be the request and the destination site will 
      need more resources to process all the data, so, there should be set an 
      optimal limit, to not impact the performance. Values range 10 - 1000."),
      '#required' => TRUE,
      '#min' => 10,
      '#max' => 1000,
      '#step' => 10,
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
          '@remotes' => Link::fromTextAndUrl('relaxed remotes', Url::fromRoute('entity.remote.collection')),
        ];
        drupal_set_message(t('All @remotes must be updated when altering the encryption settings.', $args), 'warning');
      }
    }

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $uid = trim($form_state->getValue('uid'));
    if ($form_state->getValue('mapping_type') === 'uid' && is_numeric($uid)) {
      if (!$storage->load($uid)) {
        $form_state->setErrorByName('uid', "Provided UID doesn't exist.");
      }
    }
    elseif ($form_state->getValue('mapping_type') === 'uid' && !is_numeric($uid)) {
      $form_state->setErrorByName('uid', 'Empty or wrong format for the UID field.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $mapping_type = $form_state->getValue('mapping_type');
    switch ($mapping_type) {
      case 'uid':
        $uid = $form_state->getValue('uid');
        break;
      case 'anonymous':
        $uid = 0;
        break;
      case 'uid_1':
        $uid = 1;
        break;
      default:
        $uid = NULL;
    }

    $changes_limit = $form_state->getValue('changes_limit');
    $bulk_docs_limit = $form_state->getValue('bulk_docs_limit');

    $config = $this->config('relaxed.settings');
    $config
      ->set('api_root', $form_state->getValue('api_root'))
      ->set('authentication', array_keys(array_filter($form_state->getValue('authentication'))))
      ->set('username', $form_state->getValue('username'))
      ->set('encrypt', $form_state->getValue('encrypt'))
      ->set('encrypt_profile', $form_state->getValue('encrypt_profile'))
      ->set('mapping_type', $mapping_type)
      ->set('changes_limit', $changes_limit)
      ->set('bulk_docs_limit', $bulk_docs_limit)
      ->set('uid', trim($uid))
      ->save();

    // If a password is entered then update it.
    $password = $form_state->getValue('password');
    if ($password) {
      $password = \Drupal::service('relaxed.sensitive_data.transformer')->set($password);
      $config->set('password', $password)->save();
    }
  }

}
