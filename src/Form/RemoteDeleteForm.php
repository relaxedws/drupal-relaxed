<?php

namespace Drupal\relaxed\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\WorkspacePointerInterface;

/**
 * Builds the form to delete Remote entities.
 */
class RemoteDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.remote.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['remote_pointer' => $this->entity->id()]);

    // Delete pointers first.
    /** @var WorkspacePointerInterface $pointer */
    foreach ($pointers as $pointer) {
      $deployments = $this->entityTypeManager
        ->getStorage('replication')
        ->loadByProperties(['source' => $pointer->id()]);
      $deployments += $this->entityTypeManager
        ->getStorage('replication')
        ->loadByProperties(['target' => $pointer->id()]);
      // Also mark as failed the replications that have as source or target the
      // workspace pointer that is being deleted.
      /** @var Replication $deployment */
      foreach ($deployments as $deployment) {
        $replication_status = $deployment->get('replication_status')->value;
        if (!in_array($replication_status, [Replication::QUEUED, Replication::REPLICATING])) {
          continue;
        }
        $deployment->set('fail_info', t('The workspace pointer ' .
          'does not exist, this could be cause by the missing target workspace.'));
        $deployment
          ->setReplicationStatusFailed()
          ->save();
      }
      $pointer->delete();
    }

    // Then delete the remote.
    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label()
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
