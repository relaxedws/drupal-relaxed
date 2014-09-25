<?php

namespace Drupal\relaxed\Plugin\Deriver;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DbDeriver implements ContainerDeriverInterface {

  /**
   * @var array
   */
  protected $derivatives = array();

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs an EntityDerivative object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_definition) {
    // Load all repositories.
    $entities = $this->entityManager->getStorage('workspace')->loadMultiple(NULL);
    foreach ($entities as $entity) {
      $workspace_name = $entity->name();
      // Format the plugin ID and label.
      $this->derivatives[$workspace_name] = array(
        'id' => $base_definition['id'] . ':' . $workspace_name,
        'label' => String::format($base_definition['label'], array('!db' => $workspace_name)),
      );
      // Format all URI paths.
      foreach ($base_definition['uri_paths'] as $rel => $path) {
        $this->derivatives[$workspace_name]['uri_paths'][$rel] = strtr($path, array('{db}' => $workspace_name));
      }
      // Merge in the rest of the definition.
      $this->derivatives[$workspace_name] += $base_definition;
    }

    return $this->derivatives;
  }
}
