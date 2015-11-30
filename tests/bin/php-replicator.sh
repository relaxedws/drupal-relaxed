#!/bin/sh

set -ev

# Enable dependencies.
mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test
mv ~/www/modules/relaxed/tests/php-client $TRAVIS_BUILD_DIR/

php ~/drush.phar --yes --uri=http://localhost:8081 site-install --sites-subdir=8081.localhost --account-pass=admin --db-url=mysql://root:@127.0.0.1/drupal1 standard

php ~/drush.phar --yes --uri=http://localhost:8080 pm-enable entity_test, relaxed_test || true
php ~/drush.phar --yes --uri=http://localhost:8081 pm-enable entity_test, relaxed_test || true

cd $TRAVIS_BUILD_DIR/php-client
composer install

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
php $TRAVIS_BUILD_DIR/php-client/replicate.php '{"source": {"dbname": "source"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}';
sleep 60

# Get all docs from localhost:8080 for debugging.
curl -X GET http://admin:admin@localhost:8080/relaxed/default/_all_docs

# Run the replication from localhost:8080 to localhost:8081.
php $TRAVIS_BUILD_DIR/php-client/replicate.php '{"source": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}}';
sleep 60

# Get all docs from localhost:8080 for debugging.
curl -X GET http://admin:admin@localhost:8081/relaxed/default/_all_docs

# Run the replication from localhost:8081 to CouchDB.
php $TRAVIS_BUILD_DIR/php-client/replicate.php '{"source": {"host": "localhost", "path": "relaxed", "port": 8081, "user": "replicator", "password": "replicator", "dbname": "default", "timeout": 10}, "target": {"dbname": "target"}}';
sleep 60

# Get all docs from target to check replication worked.
curl -X GET http://localhost:5984/target/_all_docs | tee /tmp/all_docs.txt

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
USERS=6
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
