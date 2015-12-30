#!/bin/sh

set -ev

phpunit --configuration ~/www/modules/relaxed/phpunit.travis.xml --bootstrap ~/www/core/tests/bootstrap.php
