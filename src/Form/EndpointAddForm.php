<?php

/**
 * @file
 * Contains \Drupal\relaxed\Form\EndpointAddForm.
 */

namespace Drupal\relaxed\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\relaxed\Plugin\EndpointManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for action add forms.
 */
class EndpointAddForm extends EndpointForm {

  /**
   * The endpoint manager.
   *
   * @var \Drupal\relaxed\Plugin\EndpointManager
   */
  protected $manager;

  /**
   * Constructs a new ActionAddForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action storage.
   * @param \Drupal\relaxed\Plugin\EndpointManager $manager
   *   The action plugin manager.
   */
  public function __construct(EntityStorageInterface $storage, EndpointManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('action'),
      $container->get('plugin.manager.endpoint')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $endpoint_id
   *   The hashed version of the endpoint ID.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL) {

    foreach ($this->manager->getDefinitions() as $id => $definition) {
      $key = Crypt::hashBase64($id);
      if ($key === $plugin_id) {
        $this->entity->setPlugin($id);
        $this->entity->set('label', $definition['label']);
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

}
