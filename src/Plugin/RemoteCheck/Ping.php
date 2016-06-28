<?php

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

  /**
   * {@inheritdoc}
   */
  public function execute(RemoteInterface $remote) {
    $url = (string) $remote->uri();
    $client = \Drupal::httpClient();
    try {
      $response = $client->request('GET', $url);
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
