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
ALL_DOCS=$(egrep -c "(1-05c159c07b7c91a42fc9d51eaec1cf16)|(2-4556e12313a1e50d12e6fc24b9094627)|(1-e52082ce92122a60a9c23778d834c7d5)|(2-17f1af0c918bbd6938cb4d7dbb1f1e07)|(2-5faf3ffd4693969e2ad87b7bf5836b7e)|(2-374da8c2b6c048c69f5cf026f7657cb1)|(2-56dca6c095d6997bc511c62e347d103a)|(1-40249f7a5def2a5838a6e7d9b3d1cd82)|(1-72a492ca669d338a9745a9c7272f0a52)|(1-526743db82240129d47e00a55bed4ff9)" /tmp/all_docs.txt > /dev/null)$?
if [ $ALL_DOCS -eq 10 ]; then exit 0; else exit 1; fi
