<?php
/**
 * @file
 * contains \Drupal\deploy\Plugin\Endpoint\WorkspaceDeriver
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
          $workspace_id = $workspace->id();
          $derivatives[$workspace_id] = [
           'label' => ucfirst($workspace_id) . ' workspace',
           'id' => 'workspace:' . $workspace_id,
           'dbname' => $workspace_id
          ] + $base_plugin_definition;
        }

        return $derivatives;
    }
}