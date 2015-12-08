<?php

/**
 * @file
 * contains \Drupal\relaxed\Plugin\Endpoint\WorkspaceEndpoint
 */

namespace Drupal\relaxed\Plugin\Endpoint;

use Drupal\relaxed\Plugin\EndpointBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Endpoint(
 *   id = "external",
 *   label = "External Endpoint"
 * )
 */
Class ExternalEndpoint extends EndpointBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $url = $this->configuration['url'];
    $parsed_url = parse_url($url);
    if ($parsed_url) {
      $this->applyParts($parsed_url);
    }
    $this->userInfo = isset($this->configuration['username']) ? $this->configuration['username'] : '';
    if (isset($this->configuration['password'])) {
      $this->userInfo .= ':' . base64_decode($this->configuration['password']);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'url' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => t('Full URL'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['url'],
    ];

    $form += parent::buildConfigurationForm($form, $form_state);
    $form['username']['#required'] = FALSE;
    $form['password']['#required'] = FALSE;

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('url'))) {
      $form_state->setErrorByName('url', t('Full URL not set.'));
    }
    if (!parse_url($form_state->getValue('url'))) {
      $form_state->setErrorByName('url', t('Invalid URL.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['url'] = $form_state->getValue('url');
    parent::submitConfigurationForm($form, $form_state);
  }
}
