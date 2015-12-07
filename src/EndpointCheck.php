<?php

namespace Drupal\relaxed;

use Drupal\relaxed\Entity\EndpointInterface;
use Drupal\relaxed\Entity\Endpoint;
use Drupal\relaxed\Plugin\EndpointCheckManager;

class EndpointCheck implements EndpointCheckInterface {

  /**
   * @var \Drupal\relaxed\Plugin\EndpointCheckManager $manager
   */
  protected $manager;

  /**
   * @param \Drupal\relaxed\Plugin\EndpointCheckManager $manager
   */
  public function __construct(EndpointCheckManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritDoc}
   */
  public function runAll() {
    $results = [];
    $endpoints = Endpoint::loadMultiple();
    foreach ($endpoints as $endpoint) {
      $results[$endpoint->id()] = $this->run($endpoint);
    }

    return $results;
  }

  /**
   * {@inheritDoc}
   */
  public function run(EndpointInterface $endpoint) {
    $results = [];
    $checks = $this->manager->getDefinitions();
    foreach ($checks as $check) {
      $checker = $this->manager->createInstance($check['id'], ['endpoint' => $endpoint]);
      $results[$check['id']] = [
        'result' => $checker->getResult(),
        'message' => $checker->getMessage(),
      ];
    }

    return $results;
  }
}
