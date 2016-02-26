<?php

/**
 * @file
 * contains \Drupal\relaxed\Plugin\Remote\WorkspaceRemote
 */

namespace Drupal\relaxed\Plugin\RemoteCheck;

use Drupal\relaxed\Entity\RemoteInterface;
use Drupal\relaxed\Plugin\RemoteCheckBase;

/**
 * @RemoteCheck(
 *   id = "ping",
 *   label = "Ping remote"
 * )
 */
Class Ping extends RemoteCheckBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(RemoteInterface $remote) {
    $url = (string) $remote->uri();
    $client = \Drupal::httpClient();
    try {
      $response = $client->request('HEAD', $url);
      if ($response->getStatusCode() === 200) {
        $this->result = true;
        $this->message = t('Remote is reachable.');
      }
      else {
        $this->message = t('Remote returns status code @status.', ['@status' => $response->getStatusCode()]);
      }
    }
    catch (\Exception $e) {
      $this->message = $e->getMessage();
      watchdog_exception('relaxed', $e);
    }
  }
}
