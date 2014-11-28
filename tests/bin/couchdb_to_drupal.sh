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
     -d '{"_id":"entity_test:1c74b3b1-6d76-4813-9e12-18d4e91e1111","_rev":"1-a2a2bb67431dd59bf85bdd6d6ff81111","id":[{"value":"8"}],"uuid":[{"value":"1c74b3b1-6d76-4813-9e12-18d4e91e1111"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 8"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"20"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":5,"ids":["a2a2bb67431dd59bf85bdd6d6ff81111","a098b75ab1947368ab7c9a632c0ea02a","35aa41544020dcf1bdcfa664e3760926","1b69807df77d61db1b9efd3742965730"]}}' \
     localhost:5984/source
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"_id":"entity_test:1c74b3b1-6d76-4813-9e12-18d4e91e2222","_rev":"2-a2a2bb67431dd59bf85bdd6d6ff82222","id":[{"value":"8"}],"uuid":[{"value":"1c74b3b1-6d76-4813-9e12-18d4e91e2222"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 8"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"20"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":5,"ids":["a2a2bb67431dd59bf85bdd6d6ff82222","a098b75ab1947368ab7c9a632c0ea02a","35aa41544020dcf1bdcfa664e3760926","1b69807df77d61db1b9efd3742965730"]}}' \
     localhost:5984/source
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"_id":"entity_test:1c74b3b1-6d76-4813-9e12-18d4e91e3333","_rev":"3-a2a2bb67431dd59bf85bdd6d6ff83333","id":[{"value":"8"}],"uuid":[{"value":"1c74b3b1-6d76-4813-9e12-18d4e91e3333"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 8"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"20"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":5,"ids":["a2a2bb67431dd59bf85bdd6d6ff83333","a098b75ab1947368ab7c9a632c0ea02a","35aa41544020dcf1bdcfa664e3760926","1b69807df77d61db1b9efd3742965730"]}}' \
     localhost:5984/source

# Check if default workspace can be accessed.
curl -X GET http://admin:admin@localhost/relaxed/default

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://localhost:5984/source", "target": "http://admin:admin@localhost/relaxed/default"}' http://localhost:5984/_replicate &
sleep 300

# Output information from couch.log.
sudo cat /var/log/couchdb/couch.log

# Output information from forensic.log.
sudo cat /var/log/apache2/forensic.log

curl -X GET http://admin:admin@localhost/relaxed/default/1c74b3b1-6d76-4813-9e12-18d4e91e1111
curl -X GET http://admin:admin@localhost/relaxed/default/1c74b3b1-6d76-4813-9e12-18d4e91e2222
curl -X GET http://admin:admin@localhost/relaxed/default/1c74b3b1-6d76-4813-9e12-18d4e91e3333