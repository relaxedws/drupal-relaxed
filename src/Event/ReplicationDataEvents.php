<?php

namespace Drupal\relaxed\Event;

/**
 * Replication events.
 */
final class ReplicationDataEvents {

  /**
   * Allows altering of normalized content data.
   *
   * This event allows modules to perform an action whenever a content entity
   *  is normalized by the ContentEntityNormalizer. The event listener method
   *  receives a \Drupal\relaxed\Event\ContentDataAlterEvent instance.
   */
  const ALTER_CONTENT_DATA = 'relaxed.alter.content';

}
