<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\multiversion\Entity\RevisionIndex;
use Drupal\relaxed\RevisionDiff\RevisionDiff;

/**
 * Tests the /db/_revs_diff resource.
 *
 * @group relaxed
 */
class RevsDiffResourceTest extends ResourceTestBase {

  public function setUp() {
    parent::setUp();

  }

  public function testPost() {
    $db = $this->workspace->name();
    $this->enableService("relaxed:revs_diff:$db", 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = array('entity_test_rev');
    foreach ($entity_types as $entity_type) {
      // Create a new test entity.
      $entity = entity_create($entity_type);
      $entity->save();

      // Update the field_test_text field.
      $entity->set(
        'field_test_text',
        array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          )
        )
      );
      $entity->save();

      // Update the name filed.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(12),
            'format' => 'plain_text',
          )
        )
      );
      $entity->save();

      // Update the name filed again.
      $entity->set(
        'name',
        array(
          0 => array(
            'value' => $this->randomString(25),
            'format' => 'plain_text',
          )
        )
      );
      $entity->save();

      $data = array();
      $revs_count = $entity->_revs_info->count();
      if ($revs_count > 0) {
        $rev_number = 1;
        $id = $entity->uuid();
        while ($rev_number <= $revs_count) {
          if ($rev = $entity->_revs_info->get($rev_number)->rev) {
            $data[$id][] = $rev;
          }
          $rev_number++;
        }
      }

      $rev_index = \Drupal::service('entity.rev_index');
      $revs_diff = new RevisionDiff($rev_index, $data);

      $serialized = $serializer->serialize($revs_diff, $this->defaultFormat);

      $response = $this->httpRequest($this->workspace->name() . '/_revs_diff', 'POST', $serialized);
      $this->assertResponse('200', 'HTTP response code is correct.');
      //$data = Json::decode($response);
    }
  }

}