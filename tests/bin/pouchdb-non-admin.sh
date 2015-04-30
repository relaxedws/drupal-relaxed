#!/bin/sh

set -ev

# Install mocha-phantomjs, pouchdb and other dependences.
npm install -g mocha-phantomjs
npm install chai
npm install es5-shim
npm install mocha
npm install pouchdb

mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/pouchdb/test-non-admin.html $TRAVIS_BUILD_DIR/../drupal/test-non-admin.html
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/pouchdb/test-non-admin.js $TRAVIS_BUILD_DIR/../drupal/test-non-admin.js
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test

# Enable dependencies.
drush en --yes entity_test, relaxed_test || true

# Create a new role, add 'perform content replication' permission to this role
# and create a user with this role.
drush role-create 'Replicator'
drush role-add-perm 'Replicator' 'perform content replication'
drush user-create replicator --mail="replicator@example.com" --password="replicator"
drush user-add-role 'Replicator' replicator

mocha-phantomjs -s localToRemoteUrlAccessEnabled=true -s webSecurityEnabled=false test-non-admin.html | tee /tmp/output.txt

test 1 -eq $(egrep -c "(2 passing)" /tmp/output.txt)
