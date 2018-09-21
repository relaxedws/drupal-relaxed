<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspaces\Entity\Workspace;
use Drupal\node\Entity\Node;

/**
 * Tests replication filters.
 *
 * @group relaxed
 */
class ReplicationFilterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
    'relaxed',
  ];

  /**
   * The logged in user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->user = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($this->user);
  }

  /**
   * Test filtering the changeset by UUIDs.
   */
  public function testUuidsFilter() {
    $container = \Drupal::getContainer();
    $changes_factory = $container->get('relaxed.changes_factory');

    $workspace = Workspace::create(['id' => 'default', 'label' => 'Default']);
    $workspace->save();

    $entity1 = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 1',
      'uid' => $this->user->id(),
    ]);
    $entity1->workspace = $workspace;
    $entity1->save();

    $entity2 = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 2',
      'uid' => $this->user->id(),
    ]);
    $entity2->workspace = $workspace;
    $entity2->save();

    $parameters = ['uuids' => [$entity1->uuid()]];
    $changes = $changes_factory->get($workspace)->filter('uuid')->parameters($parameters)->getNormal();
    $this->assertCount(1, $changes, 'Expect there is 1 entity in the changeset for UUIDs filter.');
  }

  /**
   * Test filtering the changeset with the published filter.
   */
  public function testPublishedFilter() {
    $container = \Drupal::getContainer();
    $changes_factory = $container->get('relaxed.changes_factory');

    $workspace = Workspace::create(['id' => 'default', 'label' => 'Default']);
    $workspace->save();

    $entity1 = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 1',
      'uid' => $this->user->id(),
      'status' => TRUE,
    ]);
    $entity1->workspace = $workspace;
    $entity1->save();

    $entity2 = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 2',
      'uid' => $this->user->id(),
      'status' => FALSE,
    ]);
    $entity2->workspace = $workspace;
    $entity2->save();

    $changes = $changes_factory->get($workspace)->filter('published')->getNormal();
    $this->assertCount(1, $changes, 'Expect there is 1 entity in the changeset for published filter.');
  }

  /**
   * Test filtering the changeset with the entity type filter.
   */
  public function testEntityTypeFilter() {
    $container = \Drupal::getContainer();
    $changes_factory = $container->get('relaxed.changes_factory');

    $this->drupalCreateContentType(['type' => 'article2', 'name' => 'Article2']);

    $workspace = Workspace::create(['id' => 'default', 'label' => 'Default']);
    $workspace->save();

    $entity1 = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 1',
      'uid' => $this->user->id(),
      'status' => TRUE,
    ]);
    $entity1->workspace = $workspace;
    $entity1->save();

    $entity2 = Node::create([
      'type' => 'article2',
      'title' => 'Test Entity 2',
      'uid' => $this->user->id(),
      'status' => FALSE,
    ]);
    $entity2->workspace = $workspace;
    $entity2->save();

    $parameters = ['types' => ['node.article']];
    $changes = $changes_factory->get($workspace)->filter('entity_type')->parameters($parameters)->getNormal();
    $this->assertCount(1, $changes, 'Expect there is 1 entity in the changeset for entity type filter.');
  }

}
