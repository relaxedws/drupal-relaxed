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
      $response = $client->request('GET', $url);
      $body = json_decode($response->getBody());
      if ($body->db_name) {
        $this->result = true;
        $this->message = t('This endpoint returned database %database.', ['%database' => $body->db_name]);
      }
      else {
        $this->message = t('This endpoint is reachable, but no database found');
      }
    }
    catch (\Exception $e) {
      $this->message = $e->getMessage();
      watchdog_exception('relaxed', $e);
    }
  }
}
