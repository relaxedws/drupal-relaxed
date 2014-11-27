#!/bin/sh

set -ev

# Enable dependencies.
mv $TRAVIS_BUILD_DIR/../drupal/modules/relaxed/tests/modules/relaxed_test $TRAVIS_BUILD_DIR/../drupal/modules/relaxed_test
drush en --yes relaxed_test || true

# Create a target database and do the replication.
curl -X PUT localhost:5984/target
curl -X GET http://admin:admin@localhost/relaxed/default
nohup curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d '{"source": "http://admin:admin@localhost/relaxed/default", "target": "http://localhost:5984/target"}' http://localhost:5984/_replicate &
sleep 300

# Output information useful for debugging.
sudo cat /var/log/couchdb/couch.log
sudo cat /var/log/apache2/forensic.log
curl -X GET http://localhost:5984/target/_all_docs
