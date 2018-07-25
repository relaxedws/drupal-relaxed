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
      'replication.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'replication_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('replication.settings');

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

    $config = $this->config('replication.settings');
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

    $config
      ->set('mapping_type', $mapping_type)
      ->set('changes_limit', $changes_limit)
      ->set('bulk_docs_limit', $bulk_docs_limit)
      ->set('uid', trim($uid))
      ->save();
  }

}
