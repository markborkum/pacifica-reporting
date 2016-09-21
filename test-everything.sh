#!/bin/bash -xe

# basic syntax check against all php files
find -name '*.php' | xargs -n 1 php -l
#find -iname '*.php' -and ! \( -ipath '*libraries*' -or -ipath '*migrations*' \) | xargs -n 1 phpcs -n -s --standard=myemsl_ruleset.xml
