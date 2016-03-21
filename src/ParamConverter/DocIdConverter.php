<?php

namespace Drupal\relaxed\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface;
use Drupal\multiversion\Entity\Index\UuidIndexInterface;
use Symfony\Component\Routing\Route;

class DocIdConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\UuidIndexInterface
   */
  protected $uuidIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionIndexInterface
   */
  protected $revIndex;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface
   */
  protected $revTree;

  /**
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\multiversion\Entity\Index\UuidIndexInterface $uuid_index
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   * @param \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface $rev_tree
   */
  public function __construct(EntityManagerInterface $entity_manager, UuidIndexInterface $uuid_index, RevisionIndexInterface $rev_index, RevisionTreeIndexInterface $rev_tree) {
    $this->entityManager = $entity_manager;
    $this->uuidIndex = $uuid_index;
    $this->revIndex = $rev_index;
    $this->revTree = $rev_tree;
  }

  /**
   * Converts a UUID into an existing entity.   *
   *
   * @param mixed $uuid
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string | \Drupal\Core\Entity\EntityInterface[]
   *   An array of the entity or entity revisions that was requested, if
   *   existing, or else the original UUID string.
   * @todo {@link https://www.drupal.org/node/2600374 Add test to make sure empty arrays are never returned.}
   * @todo {@link https://www.drupal.org/node/2600370 Fall back to a stub entity instead of UUID string.}
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
      $entity_type_id = $item['entity_type_id'];
      $entity_id = $item['entity_id'];
      $revision_id = $item['revision_id'];
    }
    elseif (!$rev_query && $item = $this->uuidIndex->get($uuid)) {
      $entity_type_id = $item['entity_type_id'];
      $entity_id = $item['entity_id'];
    }
    // Return the plain UUID if we're missing information.
    if (!isset($entity_id) || !$entity_type_id) {
      return $uuid;
    }

    $storage = $this->entityManager->getStorage($entity_type_id);
    if ($open_revs_query && in_array($request->getMethod(), ['GET', 'HEAD'])) {
      $open_revs = [];
      if ($open_revs_query == 'all') {
        $open_revs[] = array_keys($this->revTree->getOpenRevisions($uuid));
      }
      else {
        $open_revs = $open_revs_query;
      }

      $revision_ids = [];
      foreach ($open_revs as $open_rev) {
        if ($item = $this->revIndex->get("$uuid:$open_rev")) {
          $revision_ids[] = $item['revision_id'];
        }
      }
      $revisions = [];
      foreach ($revision_ids as $revision_id) {
        if ($revision = $storage->loadRevision($revision_id)) {
          $revisions[] = $revision;
        }
      }
      return $revisions ?: $uuid;
    }
    if ($revision_id) {
      return $storage->loadRevision($revision_id) ?: $uuid;
    }
    $entity = $storage->load($entity_id) ?: $storage->loadDeleted($entity_id);
    // Do not return stub entities.
    // @todo Needs to change as part of https://www.drupal.org/node/2600370
    if ($entity->_rev->is_stub) {
      return $uuid;
    }
    return $entity ?: $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($definition['type'] == 'relaxed:docid');
  }
}
