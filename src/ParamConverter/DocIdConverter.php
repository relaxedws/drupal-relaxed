<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\Index\UuidIndex;
use Drupal\multiversion\Entity\Index\RevisionIndex;
use Symfony\Component\Routing\Route;

class DocIdConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndex
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionIndex
   */
  protected $revIndex;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndex $uuid_index
   * @param \Drupal\multiversion\Entity\Index\RevisionIndex $rev_index
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
   * @todo Add test to make sure empty arrays are never returned.
   * @todo Fall back to a stub entity instead of UUID string when it doesn't exist.
   */
  public function convert($uuid, $definition, $name, array $defaults) {
    $request = \Drupal::request();

    // Fetch parameters.
    $open_revs_query = json_decode(urldecode($request->query->get('open_revs')));
    if (!$rev_query = $request->query->get('rev')) {
      if (!$rev_query = $request->headers->get('if-none-match')) {
        $rev_query = $request->headers->get('if-match');
      }
    }

    $entity_type_id = NULL;
    $entity_id = NULL;
    $revision_id = NULL;

    // Use the indices to resolve the entity and revision ID.
    if ($rev_query && $item = $this->revIndex->get("$uuid:$rev_query")) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
      $revision_id = $item['revision_id'];
    }
    elseif (!$rev_query && $item = $this->uuidIndex->get($uuid)) {
      $entity_type_id = $item['entity_type'];
      $entity_id = $item['entity_id'];
    }
    // Return the plain UUID if we're missing information.
    if (!$entity_id || !$entity_type_id) {
      return $uuid;
    }

    $storage = $this->entityManager->getStorage($entity_type_id);
    if ($open_revs_query && in_array($request->getMethod(), array('GET', 'HEAD'))) {
      $open_revs = array();
      if ($open_revs_query == 'all') {
        $entity = $storage->load($entity_id);
        // @todo _revs_info doesn't actually denote only open revisions.
        foreach ($entity->_revs_info as $item) {
          $open_revs[] = $item->rev;
        }
      }
      else {
        $open_revs = $open_revs_query;
      }

      $revision_ids = array();
      foreach ($open_revs as $open_rev) {
        if ($item = $this->revIndex->get("$uuid:$open_rev")) {
          $revision_ids[] = $item['revision_id'];
        }
      }
      $revisions = array();
      foreach ($revision_ids as $revision_id) {
        $revisions[] = $storage->loadRevision($revision_id);
      }
      return $revisions ?: $uuid;
    }
    if ($revision_id) {
      return $storage->loadRevision($revision_id) ?: $uuid;
    }
    return $storage->load($entity_id) ?: $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($definition['type'] == 'relaxed:docid');
  }
}
