<?php

namespace Drupal\relaxed\ParamConverter;

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
   * @return string | \Drupal\Core\Entity\EntityInterface
   *   The entity if it exists in the database or else the original UUID string.
   * @todo {@link https://www.drupal.org/node/2600370 Fall back to a stub entity instead of UUID string.}
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
