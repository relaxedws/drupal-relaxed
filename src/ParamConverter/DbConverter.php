<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class DbConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * Converts a machine name into an existing workspace entity.
   *
   * @param mixed $machine_name
   * @param mixed $definition
   * @param string $name
   * @param array $defaults
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function convert($machine_name, $definition, $name, array $defaults) {
    $workspace = $this->workspaceManager->loadByMachineName($machine_name);
    if (!$workspace) {
      $workspace = $machine_name;
    }
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($definition['type'] == 'relaxed:db');
  }
}
