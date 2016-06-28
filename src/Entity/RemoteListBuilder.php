<?php

namespace Drupal\relaxed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Provides a listing of Remote entities.
 */
class RemoteListBuilder extends ConfigEntityListBuilder {

  use UrlGeneratorTrait;

  /**
   * @var bool
   */
  protected $hasError = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Remote');
    $header['uri'] = $this->t('Url');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['data'] = [
      'label' => [
        'class' => [
          'relaxed-remote-check__status-title',
          'relaxed-remote-check__status-icon',
        ],
        'data' => $entity->label(),
      ],
      'uri' => $entity->withoutUserInfo(),
      'operations' => [
        'data' => $this->buildOperations($entity),
      ],
    ];
    $row['class'] = ['relaxed-remote-check__entry'];
    $checks = \Drupal::service('plugin.manager.remote_check')->run($entity);
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
      $row['data']['label']['class'][] = 'relaxed-remote-check__status-icon--error';
      $row['class'][] = 'color-error';
    }
    else {
      $row['data']['label']['class'][] = 'relaxed-remote-check__status-icon--ok';
      $row['class'][] = 'color-ok';
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['remotes'] = parent::render();
    $build['remotes']['table']['#attached'] = [
      'library' => [
        'relaxed/drupal.relaxed.admin',
      ],
    ];
    // Indicate to the user that there is a problem with one of the endpoints.
    if ($this->hasError) {
      drupal_set_message(
        $this->t('One or more problems were detected with your remotes. Check the the <a href=":status">status report</a> for more information.', [':status' => $this->url('system.status')]),
        'error'
      );
    }
    return $build;
  }
}
