#!/bin/sh

set -ev

# Enable dependencies.
mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
drush en --yes entity_test, relaxed_test || true

# Create a target database and do the replication.
curl -X PUT localhost:5984/target
curl -X GET http://admin:admin@localhost/relaxed/default
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://admin:admin@localhost/relaxed/default", "target": "http://localhost:5984/target"}' http://localhost:5984/_replicate &
sleep 300

curl -X GET http://localhost:5984/target/_all_docs | tee /tmp/all_docs.txt

# Analyze the output to ascertain the right revisions got replicated.
ALL_DOCS=$(egrep -c "(\"total_rows\"\:10)" /tmp/all_docs.txt)
if [ $ALL_DOCS -eq 10 ]; then exit 0; else exit 1; fi
