<?php

namespace Drupal\Tests\relaxed\Kernel\Normalizer;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\user\Entity\User;

/**
 * Tests the entity reference serialization format.
 *
 * @group relaxed
 */
class EntityReferenceItemNormalizerTest extends NormalizerTestBase {

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Tests normalization of entity reference fields that reference users.
   *
   * @todo Write a test of user ID mapping using normalization.
   *
   * @todo Write a test of entity references to other entity types, since
   * EntityReferenceItemNormalizer does special handling for users.
   */
  public function testUserReferenceFieldNormalization() {
    $this->user = User::create([
      'name' => 'user1',
      'uid' => 1,
      'mail' => 'example@example.com',
    ]);
    $this->user->save();

    $author = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@localhost',
    ]);
    $author->save();

    // Create a test entity to serialize.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomMachineName(),
      'user_id' => $author->id(),
    ]);
    $entity->save();

    list($i, $hash) = explode('-', $entity->_rev->value);
    $expected = [
      '@context' => [
        '_id' => '@id',
        '@language' => 'en'
      ],
      '@type' => 'entity_test_mulrev',
      'en' => [
        '@context' => [
          '@language' => 'en',
        ],
        'langcode' => [
          ['value' => 'en'],
        ],
        'name' => [
          [
            'value' => $entity->getName(),
          ],
        ],
        'type' => [
          ['value' => 'entity_test_mulrev'],
        ],
        'created' => [
          $this->formatExpectedTimestampItemValues($entity->created->value),
        ],
        'default_langcode' => [
          ['value' => TRUE],
        ],
        'user_id' => [
          [
            // During normalization referenced user entity will reference the
            // user from config.
            // @see Drupal\relaxed\Normalizer\EntityReferenceItemNormalizer.
            'entity_type_id' => $this->user->getEntityTypeId(),
            'target_uuid' => $this->user->uuid(),
            'username' => $this->user->label(),
          ],
        ],
        '_rev' => [
          ['value' => $entity->_rev->value],
        ],
        'non_rev_field' => [],
        'field_test_text' => [],
        'status' => [
          ['value' => TRUE],
        ],
        'non_mul_field' => [],
        'revision_default' => [
          ['value' => TRUE]
        ],
        'revision_translation_affected' => [
          ['value' => TRUE]
        ],
      ],
      '_id' => $entity->uuid(),
      '_rev' => $entity->_rev->value,
      '_revisions' => [
        'start' => 1,
        'ids' => [$hash],
      ],
    ];

    // Test normalize.
    $normalized = $this->serializer->normalize($entity);
    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, new FormattableMarkup('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertSame($denormalized->bundle(), $entity->bundle(), 'Expected entity bundle found.');
    $this->assertSame($denormalized->uuid(), $entity->uuid(), 'Expected entity UUID found.');
  }

}
