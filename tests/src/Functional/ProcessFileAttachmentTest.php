<?php

namespace Drupal\Tests\relaxed\Functional;

use Drupal\file\FileInterface;
use Drupal\workspaces\Entity\Workspace;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the use of the ProcessFileAttachment service.
 *
 * @see \Drupal\relaxed\ProcessFileAttachment
 *
 * @group relaxed
 */
class ProcessFileAttachmentTest extends BrowserTestBase {

  protected function setUp() {
    parent::setUp();
    $permissions = array_intersect([
      'administer nodes',
      'create workspace',
      'edit any workspace',
      'view any workspace',
    ], array_keys($this->container->get('user.permissions')->getPermissions()));
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $alpha = Workspace::create(['id' => 'alpha']);
    $alpha->save();
    \Drupal::service('workspaces.manager')->setActiveWorkspace($alpha);
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'relaxed', 'file', 'multiversion', 'workspaces'];

  /**
   * Test ProcessFileAttachment.
   */
  public function testProcessFileAttachment() {
    $this->container->set('workspaces.manager', NULL);
    $data = [
      'data' => 'aGVsbG8gd29ybGQK',
      'uri' => 'public://file1.txt',
      'uuid' => '6f9e1f07-e713-4840-bf95-8326c8317800',
    ];
    /** @var FileInterface $file1 */
    $file1 = \Drupal::service('relaxed.process_file_attachment')->process($data, 'base64_stream');
    $file1->save();
    $this->assertEquals('6f9e1f07-e713-4840-bf95-8326c8317800', $file1->uuid(), 'The file has the expected UUID.');

    /** @var FileInterface $file2 */
    $file2 = \Drupal::service('relaxed.process_file_attachment')->process($data, 'base64_stream');
    $file2->save();
    $this->assertEquals('6f9e1f07-e713-4840-bf95-8326c8317800', $file2->uuid(), 'The file has the expected UUID.');

    $this->assertEquals($file1->id(), $file2->id(), 'The two files have the same id.');

    file_unmanaged_delete($file2->getFileUri());
    $this->assertFalse(is_file($file2->getFileUri()));
    $this->assertFalse(is_file($file1->getFileUri()));

    /** @var FileInterface $file3 */
    $file3 = \Drupal::service('relaxed.process_file_attachment')->process($data, 'base64_stream');
    $file3->save();
    $this->assertEquals('6f9e1f07-e713-4840-bf95-8326c8317800', $file3->uuid(), 'The file has the expected UUID.');

    $this->assertEquals($file1->id(), $file3->id(), 'The two files have the same id.');
    $this->assertEquals($file2->id(), $file3->id(), 'The two files have the same id.');
    $this->assertTrue(is_file($file3->getFileUri()));
  }
}
