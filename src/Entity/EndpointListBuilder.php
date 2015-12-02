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
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\relaxed\Form\EndpointSetupForm');
    return $build;
  }

}
