#!/bin/sh

set -ev

# Install mocha-phantomjs, pouchdb and other dependences.
npm -g install npm@latest
npm config set strict-ssl false
npm install -g mocha-phantomjs
npm install ~/www/modules/relaxed/tests/pouchdb

mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/pouchdb/test.html ~/www/test.html
mv ~/www/modules/relaxed/tests/pouchdb/test.js ~/www/test.js
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test
mv ~/www/modules/relaxed/tests/fixtures/documents.txt ~/www/documents.txt

# Enable dependencies.
php ~/drush.phar en --yes entity_test, relaxed_test || true

mocha-phantomjs -s localToRemoteUrlAccessEnabled=true -s webSecurityEnabled=false test.html | tee /tmp/output.txt

test 1 -eq $(egrep -c "(2 passing)" /tmp/output.txt)
