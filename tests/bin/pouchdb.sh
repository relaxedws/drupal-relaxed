#!/bin/sh

set -ev

mv $TRAVIS_BUILD_DIR/../drupal/core/modules/system/tests/modules/entity_test $TRAVIS_BUILD_DIR/../drupal/modules/entity_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/pouchdb/test.html $TRAVIS_BUILD_DIR/../drupal/test.html
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/pouchdb/test.js $TRAVIS_BUILD_DIR/../drupal/test.js

# Enable dependencies.
drush en --yes entity_test, relaxed_test || true

mocha-phantomjs -R dot -s localToRemoteUrlAccessEnabled=true http://admin:admin@localhost/test.html | tee /tmp/output.txt

test 1 -eq $(egrep -c "(2 passing)" /tmp/output.txt)