<?php

/**
 * @file
 * Contains \Drupal\relaxed\Form\EndpointSetupForm.
 */

namespace Drupal\relaxed\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Crypt;
use Drupal\relaxed\Plugin\EndpointManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for endpoints.
 */
class EndpointSetupForm extends FormBase {

  /**
   * The endpoint plugin manager.
   *
   * @var \Drupal\relaxed\Plugin\EndpointManager
   */
  protected $manager;

  /**
   * Constructs a new EndpointSetupForm.
   *
   * @param \Drupal\relaxed\Plugin\EndpointManager $manager
   *   The endpoint plugin manager.
   */
  public function __construct(EndpointManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relaxed_endpoint_setup';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $endpoints = [];
    foreach ($this->manager->getDefinitions() as $id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $key = Crypt::hashBase64($id);
        $endpoints[$key] = $definition['label'];
      }
    }
    $form['parent'] = array(
      '#type' => 'details',
      '#title' => $this->t('Setup endpoint'),
      '#attributes' => array('class' => array('container-inline')),
      '#open' => TRUE,
    );
    $form['parent']['endpoint'] = array(
      '#type' => 'select',
      '#title' => $this->t('Endpoint'),
      '#title_display' => 'invisible',
      '#options' => $endpoints,
      '#empty_option' => $this->t('Choose endpoint type'),
    );
    $form['parent']['actions'] = array(
      '#type' => 'actions'
    );
    $form['parent']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Setup'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('endpoint')) {
      $form_state->setRedirect(
        'entity.endpoint.add_form',
        array('plugin_id' => $form_state->getValue('endpoint'))
      );
    }
  }

}
