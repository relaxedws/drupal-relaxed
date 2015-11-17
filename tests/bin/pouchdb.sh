#!/bin/sh

set -ev

# Install mocha-phantomjs, pouchdb and other dependences.
npm install ../pouchdb

mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/pouchdb/test.html ~/www/test.html
mv ~/www/modules/relaxed/tests/pouchdb/test.js ~/www/test.js
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test

# Enable dependencies.
php ~/drush.phar en --yes entity_test, relaxed_test || true

# Create a new role, add 'perform content replication' permission to this role
# and create a user with this role.
php ~/drush.phar role-create 'Replicator'
php ~/drush.phar role-add-perm 'Replicator' 'perform content replication'
php ~/drush.phar user-create replicator --mail="replicator@example.com" --password="replicator"
php ~/drush.phar user-add-role 'Replicator' replicator

mocha-phantomjs -s localToRemoteUrlAccessEnabled=true -s webSecurityEnabled=false test.html | tee /tmp/output.txt

test 1 -eq $(egrep -c "(2 passing)" /tmp/output.txt)
