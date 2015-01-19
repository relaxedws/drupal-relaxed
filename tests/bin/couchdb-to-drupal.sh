#!/bin/sh

set -ev

# Enable dependencies.
mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
drush en --yes entity_test, relaxed_test || true

# Create a target database and do the replication.
curl -X PUT localhost:5984/source

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       localhost:5984/source
done < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt

# Get all docs from couchdb db.
curl -X GET http://localhost:5984/source/_all_docs

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://localhost:5984/source", "target": "http://admin:admin@localhost/relaxed/default"}' http://localhost:5984/_replicate &
sleep 200

curl -X GET http://admin:admin@localhost/relaxed/default/_all_docs | tee /tmp/all_docs.txt

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
