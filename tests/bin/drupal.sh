#!/bin/sh

set -ev


php ./core/scripts/run-tests.sh --verbose --keep-results --color --concurrency 4 --php `which php` --sqlite /tmp/test.sqlite --url http://localhost:8080 "relaxed" | tee /tmp/test.log
export STATUS_SCRIPT=${PIPESTATUS[0]}

# Workaround so that we exit with the correct status.
STATUS_LOG=$(! egrep -i "([0-9]+ fails)|([0-9]+ exceptions)|(PHP Fatal error)|(FATAL)" /tmp/test.log > /dev/null)$?
test $STATUS_SCRIPT -eq 0 && test $STATUS_LOG -eq 0
