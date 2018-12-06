<?php

namespace Drupal\relaxed\Workspace;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\workspaces\Negotiator\WorkspaceNegotiatorInterface;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\workspaces\Entity\Workspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelaxedWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ConfigFactoryInterface $config_factory) {
    $this->workspaceManager = $workspace_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $api_root = trim($this->configFactory->get('relaxed.settings')->get('api_root'), '/');
    $path_info = trim($request->getPathInfo(), '/');

    if (!empty($api_root) && strpos($path_info, $api_root) === 0) {
      $paths = explode('/', $path_info);
      // If we have more than one part, and the second part is not an internal
      // resource, then it means the second part is the workspace ID.
      if (count($paths) > 1 && substr($paths[1], 0, 1) != '_') {
        if (Workspace::load($paths[1])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace(Request $request) {
    $path_info = trim($request->getPathInfo(), '/');
    $paths = explode('/', $path_info);

    $workspace = Workspace::load($paths[1]);
    if (!$workspace) {
      throw new NotFoundHttpException();
    }
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {}

}
