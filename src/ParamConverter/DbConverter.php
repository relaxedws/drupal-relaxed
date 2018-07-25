<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class DbConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * Converts a machine name into an existing workspace entity.
   *
   * @param string $machine_name
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function convert($machine_name, $definition, $name, array $defaults) {
    $workspace = Workspace::load($machine_name);
    if (!$workspace) {
      $methods = $defaults['_route_object']->getMethods();
      if (in_array('PUT', $methods) && $defaults['_api_resource'] == 'db') {
        $workspace = Workspace::create([
          'machine_name' => $machine_name,
          'label' => ucfirst($machine_name),
        ]);
      }
    }

    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'relaxed:db') {
      return TRUE;
    }
    return FALSE;
  }

}
