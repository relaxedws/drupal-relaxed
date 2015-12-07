<?php

namespace Drupal\relaxed;

use Drupal\relaxed\Entity\EndpointInterface;

interface EndpointCheckInterface {

  /**
   * Run checks against all endpoints.
   *
   * @return array
   */
  public function runAll();

  /**
   * Run checks against one endpoint.
   *
   * @param \Drupal\relaxed\Entity\EndpointInterface $endpoint
   * @return array
   */
  public function run(EndpointInterface $endpoint);
}
