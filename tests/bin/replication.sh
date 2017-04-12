#!/bin/sh

set -ev

mv ~/www/core/modules/system/tests/modules/entity_test ~/www/modules/entity_test
mv ~/www/modules/relaxed/tests/modules/relaxed_test ~/www/modules/relaxed_test
mv ~/www/modules/relaxed/tests/phpunit.travis.xml ~/www

php ~/drush.phar --yes --uri=http://localhost:8081 site-install --sites-subdir=8081.localhost --account-pass=admin --db-url=mysql://root:@127.0.0.1/drupal1 testing

php ~/drush.phar --yes --uri=http://localhost:8080 pm-enable entity_test, relaxed_test || true
php ~/drush.phar --yes --uri=http://localhost:8081 pm-enable entity_test, relaxed_test || true

# Check CouchDB installation.
curl http://localhost:${COUCH_PORT}

vendor/phpunit/phpunit/phpunit --verbose --debug --configuration phpunit.travis.xml --bootstrap core/tests/bootstrap.php
