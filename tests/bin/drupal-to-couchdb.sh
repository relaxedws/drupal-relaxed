#!/bin/sh

set -ev

# Enable dependencies.
mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test
php ~/drush.phar en --yes entity_test, relaxed_test || true

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       admin:admin@localhost:8080/relaxed/default;
done < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt

# Create a target database and do the replication.
curl -X PUT localhost:5984/target

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://replicator:replicator@localhost:8080/relaxed/default", "target": "http://localhost:5984/target", "worker_processes": 1}' http://localhost:5984/_replicate &
sleep 60

curl -X GET http://localhost:5984/target/_all_docs | tee /tmp/all_docs.txt

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
USERS=3
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
