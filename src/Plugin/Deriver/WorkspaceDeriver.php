<?php
/**
 * @file
 * contains \Drupal\relaxed\Plugin\Endpoint\WorkspaceDeriver
 */

namespace Drupal\relaxed\Plugin\Deriver;

use Drupal\multiversion\Entity\Workspace;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

class WorkspaceDeriver implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    $workspaces = Workspace::loadMultiple();
    foreach ($workspaces as $workspace) {
      $machine_name = $workspace->getMachineName();
      $derivatives[$machine_name] = [
          'label' => $workspace->label() . ' workspace',
          'id' => 'workspace:' . $machine_name,
          'dbname' => $machine_name
        ] + $base_plugin_definition;
    }

    return $derivatives;
  }
}
