#!/bin/bash -xe

# basic syntax check against all php files
#find -name '*.php' | xargs -n 1 php -l
#find -iname '*.php' -and ! \( -ipath '*libraries*' -or -ipath '*migrations*' -or -ipath '*websystem*' \) | xargs -n 1 phpcs -n --standard=myemsl_ruleset.xml
./vendor/bin/phpcs -n --extensions=php --ignore=*/websystem/*,*/third_party/*,*/system/*,*/migrations/*,*/libraries/*,*/logs/*,*/config/* --standard=pacifica_php_ruleset.xml application/
if [[ $CODECLIMATE_REPO_TOKEN ]]; then
phpunit --coverage-clover build/logs/clover.xml tests
./vendor/bin/test-reporter
fi
