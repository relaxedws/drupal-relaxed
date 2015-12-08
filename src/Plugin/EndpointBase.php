<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\EndpointBase.
 */

namespace Drupal\relaxed\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for Endpoint plugins.
 */
Class EndpointBase extends PluginBase implements EndpointInterface, PluginFormInterface {

  /**
   * @var string Uri scheme.
   */
  protected $scheme = '';

  /**
   * @var string Uri user info.
   */
  protected $userInfo = '';

  /**
   * @var string Uri host.
   */
  protected $host = '';

  /**
   * @var int|NULL Uri port.
   */
  protected $port = 80;

  /**
   * @var string Uri path.
   */
  protected $path = '';

  /**
   * @var string Uri query string.
   */
  protected $query = '';

  /**
   * @var string Uri fragment.
   */
  protected $fragment = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getScheme() {
    return $this->scheme;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthority() {
    if (empty($this->host)) {
      return '';
    }
    $authority = $this->host;
    if (!empty($this->userInfo)) {
      $authority = $this->userInfo . '@' . $authority;
    }
    if (!empty($this->port)) {
      $authority .= ':' . $this->port;
    }
    return $authority;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    return $this->userInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path == NULL ? '' : $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function getFragment() {
    return $this->fragment;
  }

  /**
   * {@inheritdoc}
   */
  public function withScheme($scheme) {
    $new = clone $this;
    $new->scheme = $scheme;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withUserInfo($user, $password = NULL) {
    $info = $user;
    if ($password) {
      $info .= ':' . $password;
    }

    $new = clone $this;
    $new->userInfo = $info;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withHost($host) {
    $new = clone $this;
    $new->host = $host;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withPort($port) {
    $new = clone $this;
    $new->port = $port;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withPath($path) {
    $new = clone $this;
    $new->path = $path;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withQuery($query) {
    $new = clone $this;
    $new->query = $query;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function withFragment($fragment) {
    $new = clone $this;
    $new->fragment = $fragment;
    return $new;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $uri = '';
    if (!empty($this->scheme)) {
      $uri .= $this->scheme . '://';
    }
    if (!empty($this->getAuthority())) {
      $uri .= $this->getAuthority();
    }
    if ($this->getPath() != NULL) {
      // Add a leading slash if necessary.
      if ($uri && substr($this->getPath(), 0, 1) !== '/') {
        $uri .= '/';
      }
      $uri .= $this->getPath();
    }
    if ($this->query != NULL) {
      $uri .= '?' . $this->query;
    }
    if ($this->fragment != NULL) {
      $uri .= '#' . $this->fragment;
    }
    return $uri;
  }

  /**
   * Apply parse_url parts to a URI.
   *
   * @param $parts Array of parse_url parts to apply.
   */
  protected function applyParts(array $parts) {
    $this->scheme = isset($parts['scheme'])
      ? $parts['scheme']
      : '';
    $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
    $this->host = isset($parts['host']) ? $parts['host'] : '';
    $this->port = !empty($parts['port'])
      ? $parts['port']
      : 80;
    $this->path = isset($parts['path'])
      ? $parts['path']
      : '';
    $this->query = isset($parts['query'])
      ? $parts['query']
      : '';
    $this->fragment = isset($parts['fragment'])
      ? $parts['fragment']
      : '';
    if (isset($parts['pass'])) {
      $this->userInfo .= ':' . $parts['pass'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => '',
      'password' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['username'],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('username'))) {
      $form_state->setErrorByName('username', t('Username not set.'));
    }
    if (empty($form_state->getValue('password'))) {
      $form_state->setErrorByName('password', t('Password not set.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['password'] = base64_encode($form_state->getValue('password'));
  }

}
