<?php

/**
 * @file
 * This file is used to re-generate the documents.txt file with new fixture
 * documents that are used for testing.
 *
 * Simply re-generate the documents by executing `php generate.php` from the
 * command line, inside the `tests/fixtures` directory.
 */

define('DOCUMENTS', './documents.txt');
define('FILE1', './file1.txt');

function _file_info($filename) {
  $contents = file_get_contents($filename);
  return [
    'content_type' => mime_content_type($filename),
    'digest' => 'md5-' . base64_encode(md5($contents)),
    'length' => filesize($filename),
    'data' => base64_encode($contents),
  ];
}

$docs = [
  // User entity.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'user',
    '_id' => '43155828-70db-486b-9057-f6cc905d8470',
    'en' => [
      'name' => [['value' => 'a']],
      'mail' => [['value' => 'a@foo.bar']],
      'pass' => [['value' => 'a']],
      'status' => [['value' => 1]],
    ],
  ],
  // Entity without user reference.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => '549dab6c-8e85-48d4-994d-b023ff6f22f6',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [],
    ],
  ],
  // Entity with existing user reference.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => 'f98c1d30-2db1-4628-a497-eba5e900d47e',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [['target_uuid' => '43155828-70db-486b-9057-f6cc905d8470']],
    ]
  ],
  // Entity with non-existing user reference (will be created as stub).
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => '966f2c87-e0f0-4ca4-80f3-f271e797b31e',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [['target_uuid' => '84eaf36e-e3c3-4d36-83a0-c3aa5baeb21b']],
    ],
  ],
  // Another entity with the same non-existing user reference as previous.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => '52ff018d-8834-4a3f-bcdc-1db1a264f734',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [['target_uuid' => '84eaf36e-e3c3-4d36-83a0-c3aa5baeb21b']],
    ],
  ],
  // User entity that will update first stub.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'user',
    '_id' => '84eaf36e-e3c3-4d36-83a0-c3aa5baeb21b',
    'en' => [
      'name' => [['value' => 'b']],
      'mail' => [['value' => 'b@foo.bar']],
      'pass' => [['value' => 'b']],
      'status' => [['value' => 1]],
    ],
  ],
  // Entity with existing revision.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => '1da2a674-4740-4edb-ad3d-2e243c9e6821',
    '_rev' => '1-e4af2d5d944d64db082b484bc1088d1a',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [],
    ],
  ],
  // Entity with attachment.
  [
    '@context' => [
      '_id' => '@id',
      '@language' => 'en'
    ],
    '@type' => 'entity_test_mulrev',
    '_id' => 'ad3d5c67-e82a-4faf-a7fd-c5ad3975b622',
    'en' => [
      'type' => [['value' => 'entity_test_mulrev']],
      'user_id' => [],
    ],
    '_attachments' => [
      'files/0/effb530e-5529-46ed-bec4-1b8f05c274d6/public/file1.txt' => _file_info(FILE1),
    ],
  ],
];

// Start by emptying the file.
file_put_contents(DOCUMENTS, NULL);

// Encode and append all documents to the file.
foreach ($docs as $doc) {
  file_put_contents(DOCUMENTS, json_encode($doc) . PHP_EOL, FILE_APPEND);
}
