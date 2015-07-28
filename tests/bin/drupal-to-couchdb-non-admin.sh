#!/bin/sh

set -ev
DRUPAL_ROOT=$1
DRUPAL_DOMAIN=$2

# Enable dependencies.
cp -R $DRUPAL_ROOT/core/modules/system/tests/modules/entity_test $DRUPAL_ROOT/modules/
cp -R $DRUPAL_ROOT/modules/relaxed/tests/modules/relaxed_test $DRUPAL_ROOT/modules/

drush en --yes entity_test, relaxed_test || true

# Create a new role, add 'perform content replication' permission to this role
# and create a user with this role.
drush role-create 'Replicator'
drush role-add-perm 'Replicator' 'perform content replication'
drush user-create replicator --mail="replicator@example.com" --password="replicator"
drush user-add-role 'Replicator' replicator

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       $DRUPAL_DOMAIN/relaxed/default;
  sleep 2;
done < $DRUPAL_ROOT/modules/relaxed/tests/fixtures/documents.txt

# Create a target database and do the replication.
curl -X PUT localhost:5984/target

# Split the username:password from host in drupal_domain
IFS='@' read -ra URL <<< "$DRUPAL_DOMAIN"

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://replicator:replicator@${URL[1]}/relaxed/default", "target": "http://localhost:5984/target", "worker_processes": 1}' http://localhost:5984/_replicate &
sleep 120

curl -X GET http://localhost:5984/target/_all_docs | tee /tmp/all_docs.txt


COUNT=$(wc -l < $DRUPAL_ROOT/modules/relaxed/tests/fixtures/documents.txt)

#-----------------------------------
sudo cat /var/log/couchdb/couch.log
#-----------------------------------
sudo cat /var/log/apache2/error.log
#-----------------------------------

USERS=3
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
