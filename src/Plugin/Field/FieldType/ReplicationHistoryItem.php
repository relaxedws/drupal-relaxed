<?php

namespace Drupal\relaxed\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "replication_history",
 *   label = @Translation("Replication history"),
 *   description = @Translation("History information for a replication."),
 *   list_class = "\Drupal\relaxed\Plugin\Field\FieldType\ReplicationHistoryItemList",
 *   no_ui = TRUE
 * )
 */
class ReplicationHistoryItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'session_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['doc_write_failures'] = DataDefinition::create('integer')
      ->setLabel(t('Write failures'))
      ->setDescription(t('Number of failed document writes'))
      ->setRequired(FALSE);

    $properties['docs_read'] = DataDefinition::create('integer')
      ->setLabel(t('Documents read'))
      ->setDescription(t('Number of documents read.'))
      ->setRequired(FALSE);

    $properties['docs_written'] = DataDefinition::create('integer')
      ->setLabel(t('Documents written'))
      ->setDescription(t('Number of documents written.'))
      ->setRequired(FALSE);

    $properties['end_last_seq'] = DataDefinition::create('integer')
      ->setLabel(t('End sequence'))
      ->setDescription(t('Sequence ID where the replication ended.'))
      ->setRequired(FALSE);

    $properties['end_time'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('End time'))
      ->setDescription(t('Date and time when replication ended.'))
      ->setRequired(FALSE);

    $properties['missing_checked'] = DataDefinition::create('integer')
      ->setLabel(t('Missing checked'))
      ->setDescription(t('Number of missing documents checked.'))
      ->setRequired(FALSE);

    $properties['missing_found'] = DataDefinition::create('integer')
      ->setLabel(t('Missing found'))
      ->setDescription(t('Number of missing documents found.'))
      ->setRequired(FALSE);

    $properties['recorded_seq'] = DataDefinition::create('integer')
      ->setLabel(t('Recorded sequence'))
      ->setDescription(t('Recorded intermediate sequence.'))
      ->setRequired(FALSE);

    $properties['session_id'] = DataDefinition::create('string')
      ->setLabel(t('Session ID'))
      ->setDescription(t('Unique session ID for the replication.'))
      ->setRequired(TRUE);

    $properties['start_last_seq'] = DataDefinition::create('integer')
      ->setLabel(t('Start sequence'))
      ->setDescription(t('Sequence ID where the replication started.'))
      ->setRequired(FALSE);

    $properties['start_time'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Start time'))
      ->setDescription(t('Date and time when replication started.'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'doc_write_failures' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
        'docs_read' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
        'docs_written' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
        'end_last_seq' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'end_time' => array(
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ),
        'missing_checked' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
        'missing_found' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ),
        'recorded_seq' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
          'default' => 0,
        ),
        'session_id' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ),
        'start_last_seq' => array(
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'start_time' => array(
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ),
      ),
    );
  }

}
