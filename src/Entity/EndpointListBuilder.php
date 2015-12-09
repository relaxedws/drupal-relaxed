<?php

/**
 * @file
 * Contains \Drupal\relaxed\Entity\EndpointListBuilder.
 */

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Provides a listing of Endpoint entities.
 */
class EndpointListBuilder extends ConfigEntityListBuilder {

  use UrlGeneratorTrait;

  /**
   * @var bool
   */
  protected $hasError = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Endpoint');
    $header['id'] = [
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'data' => $this->t('Machine name'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['data'] = [
      'label' => [
        'class' => [
          'relaxed-endpoint-check__status-title',
          'relaxed-endpoint-check__status-icon',
        ],
        'data' => $entity->label(),
      ],
      'id' => $entity->id(),
      'operations' => [
        'data' => $this->buildOperations($entity),
      ],
    ];
    $row['class'] = ['relaxed-endpoint-check__entry'];
    $checks = \Drupal::service('plugin.manager.endpoint_check')->run($entity);
    $row_has_error = FALSE;
    foreach ($checks as $check) {
      switch ($check['result']) {
        case FALSE:
          $row_has_error = TRUE;
          break;
      }
    }

    if ($row_has_error) {
      $this->hasError = TRUE;
      $row['data']['label']['class'][] = 'relaxed-endpoint-check__status-icon--error';
      $row['class'][] = 'color-error';
    }
    else {
      $row['data']['label']['class'][] = 'relaxed-endpoint-check__status-icon--ok';
      $row['class'][] = 'color-ok';
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['endpoints'] = parent::render();
    $build['endpoints']['table']['#attached'] = [
      'library' => [
        'relaxed/drupal.relaxed.admin',
      ],
    ];
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\relaxed\Form\EndpointSetupForm');

    // Indicate to the user that there is a problem with one of the endpoints.
    if ($this->hasError) {
      drupal_set_message(
        $this->t('One or more problems were detected with your endpoints. Check the the <a href=":status">status report</a> for more information.', [':status' => $this->url('system.status')]),
        'error'
      );
    }
    return $build;
  }

}
