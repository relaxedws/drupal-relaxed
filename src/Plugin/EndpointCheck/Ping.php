<?php

/**
 * @file
 * contains \Drupal\relaxed\Plugin\Endpoint\WorkspaceEndpoint
 */

namespace Drupal\relaxed\Plugin\EndpointCheck;

use Drupal\relaxed\Entity\EndpointInterface;
use Drupal\relaxed\Plugin\EndpointCheckBase;

/**
 * @EndpointCheck(
 *   id = "ping",
 *   label = "Ping endpoint"
 * )
 */
Class Ping extends EndpointCheckBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EndpointInterface $endpoint) {
    $url = (string) $endpoint->getPlugin();
    $client = \Drupal::httpClient();
    try {
      $response = $client->request('HEAD', $url);
      if ($response->getStatusCode() === 200) {
        $this->result = true;
        $this->message = t('Endpoint is reachable.');
      }
      else {
        $this->message = t('Endpoint returns status code @status.', ['@status' => $response->getStatusCode()]);
      }
    }
    catch (\Exception $e) {
      $this->message = $e->getMessage();
      watchdog_exception('relaxed', $e);
    }
  }
}
