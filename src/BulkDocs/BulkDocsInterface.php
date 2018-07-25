<?php

namespace Drupal\relaxed\BulkDocs;

interface BulkDocsInterface {

  /**
   * @param boolean $new_edits
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function newEdits($new_edits);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function setEntities($entities);

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  public function getEntities();

  /**
   * @return \Drupal\relaxed\BulkDocs\BulkDocsInterface
   */
  public function save();

  /**
   * @return array
   */
  public function getResult();

}
