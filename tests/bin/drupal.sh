#!/bin/sh

set -ev


php ./core/scripts/run-tests.sh --verbose --keep-results --color --concurrency 4 --php `which php` --sqlite /tmp/test.sqlite --url http://localhost:8080 --module relaxed
