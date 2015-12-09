<?php

/**
 * @file
 * Contains \Drupal\relaxed\Entity\EndpointListBuilder.
 */

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Endpoint entities.
 */
class EndpointListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Endpoint');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['data'] = [
      'label' => $entity->label(),
      'id' => $entity->id(),
      'operations' => [
        'data' => $this->buildOperations($entity),
      ],
    ];
    $row['class'] = 'color-error';
    $checks = \Drupal::service('plugin.manager.endpoint_check')->run($entity);
    foreach ($checks as $check) {
      if ($check['result'] === true) {
        $row['class'] = 'color-success';
      }
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['endpoints'] = parent::render();
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\relaxed\Form\EndpointSetupForm');
    return $build;
  }

}
