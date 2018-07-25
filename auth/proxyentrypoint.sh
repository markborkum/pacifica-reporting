#!/bin/bash -xe

sed 's/${PROXY_ADDR}/'${PROXY_ADDR}'/;'\
's/${PROXY_PORT}/'${PROXY_PORT}'/;'\
's/${FILE_REDIRECT_ADDR}/'${FILE_REDIRECT_ADDR}'/;'\
's/${FILE_REDIRECT_PORT}/'${FILE_REDIRECT_PORT}'/;' \
    /etc/nginx/conf.d/proxy.template > /etc/nginx/conf.d/proxy.conf

sed 's/${BACKEND_ADDR}/'${UPLOAD_STATUS_ADDR}'/;'\
's/${BACKEND_PORT}/'${UPLOAD_STATUS_PORT}'/;'\
's/${SITE_NAME}/status/;' \
    /etc/nginx/conf.d/codeigniter.template > /etc/nginx/conf.d/upload_status.conf

sed 's/${BACKEND_ADDR}/'${REPORTING_ADDR}'/;'\
's/${BACKEND_PORT}/'${REPORTING_PORT}'/;'\
's/${SITE_NAME}/reporting/;' \
    /etc/nginx/conf.d/codeigniter.template > /etc/nginx/conf.d/reporting.conf


nginx -g 'daemon off;'
