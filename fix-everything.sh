#!/bin/bash -xe

# basic syntax check against all php files
#find -name '*.php' | xargs -n 1 php -l
#find -iname '*.php' -and ! \( -ipath '*libraries*' -or -ipath '*migrations*' -or -ipath '*websystem*' \) | xargs -n 1 phpcs -n --standard=myemsl_ruleset.xml
phpcbf -n --extensions=php --ignore=*/websystem/*,*/third_party/*,*/system/*,*/migrations/*,*/libraries/*,*/logs/* --standard=myemsl_ruleset.xml application/
