<?php

namespace Drupal\Tests\relaxed\Integration;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\multiversion\Entity\Workspace;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Relaxed\Replicator\ReplicationTask;
use Relaxed\Replicator\Replicator;

require_once __DIR__ . '/ReplicationTestBase.php';

/**
 * @group relaxed
 */
class ContentReplicationTest extends ReplicationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'system',
    'rest',
    'key_value',
    'multiversion',
    'relaxed',
    'workspace',
    'replication',
    'entity_test',
    'relaxed_test',
    'user',
    'text',
    'filter',
    'link',
    'file',
    'language',
    'content_translation',
    'node',
    'taxonomy',
    'block',
    'block_content',
    'comment',
    'shortcut',
    'field',
    'datetime',
    'migrate',
    'migrate_drupal',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  private $multiversionManager;

  /**
   * @var \Drupal\workspace\ReplicatorManager
   */
  private $replicatorManager;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  private $live;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  private $stage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->installConfig([
      'user',
      'node',
      'taxonomy',
      'block',
      'block_content',
      'comment',
      'shortcut',
      'language',
      'field',
      'migrate',
      'migrate_drupal',
      'multiversion',
      'workspace',
      'replication',
      'relaxed',
      'relaxed_test',
      ]);
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('key_value', ['key_value_sorted']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('shortcut');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    $this->multiversionManager = $this->container->get('multiversion.manager');
    $this->multiversionManager->enableEntityTypes();

    // Create a new workspace.
    $stage = Workspace::create(['machine_name' => 'stage', 'label' => 'Stage', 'type' => 'basic']);
    $stage->set('upstream', 1);
    $stage->save();
    $live = Workspace::load(1);
    $live->set('upstream', 2);
    $live->save();

    $this->live = $live;
    $this->stage = $stage;

    // Create a new language.
    ConfigurableLanguage::createFromLangcode('ro')->save();

    $this->multiversionManager->setActiveWorkspaceId(1);

    // Add comment type.
    $this->entityTypeManager->getStorage('comment_type')->create([
      'id' => 'comment',
      'label' => 'Comment',
      'target_entity_type_id' => 'node',
    ])->save();

    // Add comment field to content.
    $this->entityTypeManager->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'comment',
      'type' => 'comment',
      'settings' => [
        'comment_type' => 'comment',
      ]
    ])->save();

    // Create a page node type.
    $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Add comment field to article content.
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager->getStorage('field_config')->create([
      'field_name' => 'comment',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Comment settings',
    ]);
    $field->save();

    // Create a block content type.
    $this->entityTypeManager->getStorage('block_content_type')->create([
      'id' => 'basic',
      'label' => 'Basic block',
    ])->save();

    $account = User::create([
      'name' => 'replicator',
      'status' => 1,
      'roles' => ['replicator'],
    ]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);
  }

  function testTranslatedContentReplication() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $taxonomy_term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $block_content_storage = $this->entityTypeManager->getStorage('block_content');
    $comment_storage = $this->entityTypeManager->getStorage('comment');
    $shortcut_storage = $this->entityTypeManager->getStorage('shortcut');

    $node = $node_storage->create([
      'type' => 'article',
      'title' => 'New article',
    ]);
    $node->save();
    // Add Romanian translation.
    $node_romanian = $node->addTranslation('ro');
    $node_romanian->set('title', 'Articol nou');
    $node_romanian->save();

    $term = $taxonomy_term_storage->create([
      'name' => 'Book',
      'vid' => 'tags',
    ]);
    $term->save();
    // Add Romanian translation.
    $term_romanian = $term->addTranslation('ro');
    $term_romanian->set('name', 'Carte');
    $term_romanian->save();

    $block = $block_content_storage->create([
      'info' => 'About the author',
      'type' => 'test',
    ]);
    $block->save();
    // Add Romanian translation.
    $block_romanian = $block->addTranslation('ro');
    $block_romanian->set('info', 'Despre autor');
    $block_romanian->save();

    $comment = $comment_storage->create([
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'How much wood would a woodchuck chuck',
      'mail' => 'someone@example.com',
    ]);
    $comment->save();
    // Add Romanian translation.
    $comment_romanian = $comment->addTranslation('ro');
    $comment_romanian->set('subject', 'Un caricaturist a caricaturizat o caricatura');
    $comment_romanian->save();

    $shortcut = $shortcut_storage->create([
      'shortcut_set' => 'default',
      'title' => 'Story',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ]);
    $shortcut->save();
    // Add Romanian translation.
    $shortcut_romanian = $shortcut->addTranslation('ro');
    $shortcut_romanian->set('title', 'Poveste');
    $shortcut_romanian->save();

    $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => 'replicator']);
    $account = reset($accounts);
    $this->assertTrue($account instanceof UserInterface, 'Replicator user account has been loaded successfully.');
    if ($account) {
      $this->container->get('current_user')->setAccount($account);
    }

    // Run CouchDB to Drupal replication with PHP replicator.
//    $source_info = '"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}';
//    $target_info = '"target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "stage", "timeout": 60}';
//    $this->phpReplicate('{' . $source_info . ',' . $target_info . '}');
//    $this->assertAllDocsNumber('http://replicator:replicator@localhost:8080/relaxed/stage/_all_docs', 4);

//    $test_connection_key = TestDatabase::getConnection()->getKey();
//    Database::setActiveConnection($test_connection_key);
//    $source_info = '"source": {"host": "www.drupal8dev.loc", "path": "relaxed", "port": 80, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}';
//    $target_info = '"target": {"host": "www.drupal8dev.loc", "path": "relaxed", "port": 80, "user": "replicator", "password": "replicator", "dbname": "stage", "timeout": 60}';
//    $this->phpReplicate('{' . $source_info . ',' . $target_info . '}');

    $source = CouchDBClient::create(json_decode('{"host": "www.drupal8dev.loc", "path": "relaxed", "port": 80, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 60}', TRUE));
    $target = CouchDBClient::create(json_decode('{"host": "www.drupal8dev.loc", "path": "relaxed", "port": 80, "user": "replicator", "password": "replicator", "dbname": "stage", "timeout": 60}', TRUE));

    $task = new ReplicationTask(null, false, null, null, false, null, 10000, 10000, false, "all_docs", 0, 2, 2);
    $replicator = new Replicator($source, $target, $task);

    $replicator->startReplication();

    $this->assertAllDocsNumber('http://replicator:replicator@www.drupal8dev.loc/relaxed/stage/_all_docs', 4);


    $this->multiversionManager->setActiveWorkspaceId(2);
  }

}