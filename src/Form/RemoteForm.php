<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * Class RemoteForm.
 */
class RemoteForm extends EntityForm {
  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!\Drupal::moduleHandler()->moduleExists('workspace')) {
      drupal_set_message($this->t('You have to install the <a href=":url">Workspace</a> module prior to setting up new workspaces.', [':url' => 'https://drupal.org/project/workspace']), 'warning');
      return [];
    }
    $username = $this->configFactory()->get('relaxed.settings')->get('username');
    $password = $this->configFactory()->get('relaxed.settings')->get('password');

    if (empty($username) || empty($password)) {
      drupal_set_message('You must set a username and password before adding a remote.', 'error');
      return $this->redirect('relaxed.settings_form');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $remote = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $remote->label(),
      '#description' => $this->t("Label for the Remote."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $remote->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\relaxed\Entity\Remote::load',
      ),
      '#disabled' => !$remote->isNew(),
    );

    $form['uri'] = [
      '#type' => 'textfield',
      '#title' => t('Full URL'),
      '#required' => TRUE,
      '#default_value' => (string) $remote->withoutUserInfo(),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $remote->username(),
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => t('Password'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $remote = $this->entity;
    $uri = new Uri($remote->get('uri'));
    $uri = $uri->withUserInfo(
      $form_state->getValue('username'),
      $form_state->getValue('password')
    );
    $encoded = base64_encode($uri);
    $remote->set('uri', $encoded);
    $status = $remote->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Remote.', [
          '%label' => $remote->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Remote.', [
          '%label' => $remote->label(),
        ]));
    }
    $form_state->setRedirectUrl($remote->urlInfo('collection'));
  }

}
