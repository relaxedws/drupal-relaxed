#!/bin/sh

set -ev

# Enable dependencies.
mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
drush en --yes entity_test, relaxed_test || true

# Create a new role, add 'perform content replication' permission to this role
# and create a user with this role.
drush role-create 'Replicator'
drush role-add-perm 'Replicator' 'perform content replication'
drush user-create replicator --mail="replicator@example.com" --password="replicator"
drush user-add-role 'Replicator' replicator

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

drush cr

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://localhost:5984/source", "target": "http://replicator:replicator@drupal.loc/relaxed/default", "worker_processes": 1}' http://localhost:5984/_replicate &
sleep 60

curl -X GET http://admin:admin@drupal.loc/relaxed/default/_all_docs | tee /tmp/all_docs.txt

#-----------------------------------
sudo cat /var/log/couchdb/couch.log
#-----------------------------------
sudo cat /var/log/apache2/error.log
#-----------------------------------

COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
USERS=3
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
