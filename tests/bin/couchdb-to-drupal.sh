#!/bin/sh

set -ev

# Enable dependencies.
mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
drush en --yes entity_test, relaxed_test || true

# Create a target database and do the replication.
curl -X PUT localhost:5984/source

# Crete three new documents.
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"_id":"entity_test.1111b3b1-6d76-4813-9e12-18d4e91e1111","_rev":"1-a2a2bb67431dd59bf85bdd6d6ff81111","id":[{"value":"1"}],"uuid":[{"value":"1111b3b1-6d76-4813-9e12-18d4e91e1111"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 1"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"1"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":1,"ids":["a2a2bb67431dd59bf85bdd6d6ff81111"]}}' \
     localhost:5984/source
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"_id":"entity_test.2222b3b1-6d76-4813-9e12-18d4e91e2222","_rev":"2-a2a2bb67431dd59bf85bdd6d6ff82222","id":[{"value":"2"}],"uuid":[{"value":"2222b3b1-6d76-4813-9e12-18d4e91e2222"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 2"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"2"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":2,"ids":["35aa41544020dcf1bdcfa664e3760926","1b69807df77d61db1b9efd3742965730"]}}' \
     localhost:5984/source
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"_id":"entity_test.3333b3b1-6d76-4813-9e12-18d4e91e3333","_rev":"3-a2a2bb67431dd59bf85bdd6d6ff83333","id":[{"value":"3"}],"uuid":[{"value":"3333b3b1-6d76-4813-9e12-18d4e91e3333"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 3"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"3"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":3,"ids":["a098b75ab1947368ab7c9a632c0ea02a","35aa41544020dcf1bdcfa674e3760926"]}}' \
     localhost:5984/source

# Get all docs from couchdb db.
curl -X GET http://localhost:5984/source/_all_docs

# Check if default workspace can be accessed.
curl -X GET http://admin:admin@localhost/relaxed/default

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://localhost:5984/source", "target": "http://admin:admin@localhost/relaxed/default"}' http://localhost:5984/_replicate &
sleep 300

curl -X GET http://admin:admin@localhost/relaxed/default/_all_docs | tee /tmp/all_docs.txt

# Analyze the output to ascertain the right revisions got replicated.
ALL_DOCS=$(egrep -c "(\"total_rows\"\:3)" /tmp/all_docs.txt)
if [ $ALL_DOCS -eq 3 ]; then exit 0; else exit 1; fi
