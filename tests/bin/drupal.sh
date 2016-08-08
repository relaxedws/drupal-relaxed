#!/bin/sh

set -ev

php ./core/scripts/run-tests.sh --url http://localhost:8080 --dburl mysql://root:@127.0.0.1/drupal0 --color --keep-results --color --concurrency 31 --sqlite /tmp/test.sqlite --php `which php` --verbose --directory modules/relaxed
