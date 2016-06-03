#!/bin/sh

set -ev

# Enable dependencies.
mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test

php ~/drush.phar --yes --uri=http://localhost:8081 site-install --sites-subdir=8081.localhost --account-pass=admin --db-url=mysql://root:@127.0.0.1/drupal1 testing

php ~/drush.phar --yes --uri=http://localhost:8080 pm-enable entity_test, relaxed_test || true
php ~/drush.phar --yes --uri=http://localhost:8081 pm-enable entity_test, relaxed_test || true

# Create a source and target CouchDB database and do the replication.
curl -X PUT localhost:5984/source
curl -X PUT localhost:5984/target

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       localhost:5984/source;
done < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt

# Get all docs from source for debugging.
curl -X GET http://localhost:5984/source/_all_docs

# Run the replication from CouchDB to localhost:8080.
curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": {"dbname": "source"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}}' http://replicator:replicator@localhost:8080/relaxed/_replicate

# Get all docs from localhost:8080 for debugging.
curl -X GET http://admin:admin@localhost:8080/relaxed/live/_all_docs

# Run the replication from localhost:8080 to localhost:8081.
curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}}' http://replicator:replicator@localhost:8080/relaxed/_replicate

# Get all docs from localhost:8080 for debugging.
curl -X GET http://admin:admin@localhost:8081/relaxed/live/_all_docs

# Run the replication from localhost:8081 to CouchDB.
curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "live", "timeout": 10}, "target": {"dbname": "target"}}' http://replicator:replicator@localhost:8080/relaxed/_replicate

# Get all docs from target to check replication worked.
curl -X GET http://localhost:5984/target/_all_docs | tee /tmp/all_docs.txt

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
echo $COUNT
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
