#!/bin/sh

set -ev

# Enable dependencies.
mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test
mv ~/www/modules/relaxed/tests/php-client $TRAVIS_BUILD_DIR/
php ~/drush.phar en --yes entity_test, relaxed_test || true

# Create a new role, add 'perform content replication' permission to this role
# and create a user with this role.
php ~/drush.phar role-create 'Replicator'
php ~/drush.phar role-add-perm 'Replicator' 'perform content replication'
php ~/drush.phar user-create replicator --mail="replicator@example.com" --password="replicator"
php ~/drush.phar user-add-role 'Replicator' replicator

cd $TRAVIS_BUILD_DIR/php-client
composer install

# Create a target database and do the replication.
curl -X PUT localhost:5984/source

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       localhost:5984/source;
done < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt

# Get all docs from couchdb db.
curl -X GET http://localhost:5984/source/_all_docs

# Run the replication.
php $TRAVIS_BUILD_DIR/php-client/replicate.php '{"source": {"dbname": "source"}, "target": {"host": "localhost", "path": "relaxed", "port": 8080, "user": "replicator", "password": "replicator", "dbname": "default"}}';
sleep 60

curl -X GET http://admin:admin@localhost:8080/relaxed/default/_all_docs | tee /tmp/all_docs.txt

#-----------------------------------
sudo cat /var/log/apache2/error.log
#-----------------------------------

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
USERS=3
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
