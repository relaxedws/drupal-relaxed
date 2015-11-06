<?php

namespace Drupal\relaxed\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * The replication log entity type.
 *
 * @ContentEntityType(
 *   id = "replication_log",
 *   label = @Translation("Replication log"),
 *   handlers = {
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\relaxed\Entity\ReplicationLogAccessControlHandler",
 *   },
 *   base_table = "replication_log",
 *   revision_table = "replication_log_revision",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   local = TRUE,
 * )
 */
class ReplicationLog extends ContentEntityBase implements ReplicationLogInterface {

  /**
   * {@inheritdoc}
   */
  public function getHistory() {
    return $this->get('history')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setHistory($history) {
    $this->set('history', $history);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->get('session_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    $this->set('session_id', $session_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLastSeq() {
    return $this->get('source_last_seq')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceLastSeq($source_last_seq) {
    $this->set('source_last_seq', $source_last_seq);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the replication log entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the replication log entity.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The local revision ID of the replication log entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['history'] = BaseFieldDefinition::create('replication_history')
      ->setLabel(t('Replication log history'))
      ->setDescription(t('The version id of the test entity.'))
      ->setReadOnly(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['session_id'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('Replication session ID'))
      ->setDescription(t('The unique session ID of the last replication. Shortcut to the session_id in the last history item.'))
      ->setReadOnly(TRUE);

    $fields['source_last_seq'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last processed checkpoint'))
      ->setDescription(t('The last processed checkpoint. Shortcut to the source_last_seq in the last history item.'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}
