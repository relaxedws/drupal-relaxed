<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\UuidIndex;
use Drupal\multiversion\Entity\RevisionIndex;
use Symfony\Component\Routing\Route;

class DocIdConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\UuidIndex
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\RevisionIndex
   */
  protected $revIndex;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\UuidIndex $uuid_index
   * @param \Drupal\multiversion\Entity\RevisionIndex $rev_index
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndex $uuid_index, RevisionIndex $rev_index) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revIndex = $rev_index;
  }

  /**
   * Converts a UUID into an existing entity.
   *
   * @return string | \Drupal\Core\Entity\EntityInterface[]
   *   An array of the entity or entity revisions that was requested, if
   *   existing, or else the original UUID string.
   */
  public function convert($uuid, $definition, $name, array $defaults) {
    $entity_type_id = NULL;
    $entity_id = NULL;
    $revision_ids = array();
    $entities = array();
    $request = \Drupal::request();

    // Fetch parameters.
    $open_revs_query = trim($request->query->get('open_revs'), '[]');
    if (!$rev_query = $request->query->get('rev')) {
      $rev_query = $request->headers->get('if-none-match');
    }

    // Use the indices to resolve the entity and revision ID.
    if ($rev_query && $item = $this->revIndex->get("$uuid:$rev_query")) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
      $revision_ids[] = $item['revision_id'];
    }
    elseif ($item = $this->uuidIndex->get($uuid)) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
    }
    // Return the plain UUID if we're missing information.
    if (!$entity_id || !$entity_type_id) {
      return $uuid;
    }

    $storage = $this->entityManager->getStorage($entity_type_id);
    if ($open_revs_query) {
      $open_revs = array();
      if ($open_revs_query == 'all') {
        $entity = $storage->load($entity_id);
        // @todo _revs_info doesn't actually denote only open revisions.
        foreach ($entity->_revs_info as $item) {
          $open_revs[] = $item->rev;
        }
      }
      else {
        $open_revs = explode(',', $open_revs_query);
      }
      foreach ($open_revs as $open_rev) {
        if ($item = $this->revIndex->get("$uuid:$open_rev")) {
          $revision_ids[] = $item['revision_id'];
        }
      }
    }
    if ($revision_ids) {
      foreach ($revision_ids as $revision_id) {
        $entities[] = $storage->loadRevision($revision_id);
      }
      return $entities ?: $uuid;
    }
    return $storage->loadMultiple(array($entity_id)) ?: $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($name == 'docid');
  }
}
