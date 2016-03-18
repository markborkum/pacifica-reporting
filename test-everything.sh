#!/bin/bash -xe

# basic syntax check against all php files
find -name '*.php' | xargs -n 1 php -l
