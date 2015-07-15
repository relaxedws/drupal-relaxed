#!/bin/sh

set -ev

mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal2/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal2/modules/entity_test

cp -r $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
mv $TRAVIS_BUILD_DIR/../drupal2/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal2/modules/relaxed_test

# Enable dependencies for the first drupal instance.
drush en --yes entity_test, relaxed_test || true

# Enable dependencies for the second drupal instance.
cd $TRAVIS_BUILD_DIR/../drupal2
drush en --yes entity_test, relaxed_test || true

# Load documents from documents.txt and save them in the 'source' database.
while read document
do
  curl -X POST \
       -H "Content-Type: application/json" \
       -d "$document" \
       admin:admin@drupal.loc/relaxed/default;
  sleep 2;
done < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt

drush cr

# Run the replication.
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://admin:admin@drupal.loc/relaxed/default", "target": "http://admin:admin@drupal2.loc/relaxed/default"}' http://localhost:5984/_replicate &
sleep 120

curl -X GET http://admin:admin@drupal2.loc/relaxed/default/_all_docs | tee /tmp/all_docs.txt

#-----------------------------------
sudo cat /var/log/couchdb/couch.log
#-----------------------------------
sudo cat /var/log/apache2/error.log
#-----------------------------------
sudo cat /var/log/apache2/forensic.log
#-----------------------------------


COUNT=$(wc -l < $TRAVIS_BUILD_DIR/tests/fixtures/documents.txt)
USERS=4
COUNT=$(($COUNT + $USERS));
test 1 -eq $(egrep -c "(\"total_rows\"\:$COUNT)" /tmp/all_docs.txt)
