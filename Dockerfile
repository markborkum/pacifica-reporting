FROM php:5.6-apache

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -y install \
       apt-utils \
       unzip \
       sqlite3 \
       php5-pgsql \
       php5-sqlite \
       vim

    #DEBIAN_FRONTEND=noninteractive apt-get -y upgrade

RUN a2enmod rewrite
#ENV CI_ENV unit_testing
#ENV CI_ROOTED true

ENV APACHE_RUN_USER   www-data
ENV APACHE_RUN_GROUP  www-data
ENV APACHE_PID_FILE   /var/run/apache2.pid
ENV APACHE_RUN_DIR    /var/run/apache2
ENV APACHE_LOCK_DIR   /var/lock/apache2
ENV APACHE_LOG_DIR    /var/log/apache2

EXPOSE 80

COPY websystem/system /var/www/html/system
COPY websystem/index.php /var/www/html/
COPY tests /var/www/html/tests
RUN cat tests/database/myemsl_metadata-eus.sql | sqlite3 tests/database/myemsl_metadata-eus.sqlite3
RUN cat tests/database/myemsl_metadata-myemsl.sql | sqlite3 tests/database/myemsl_metadata-myemsl.sqlite3
RUN cat tests/database/myemsl_metadata-website_prefs.sql | sqlite3 tests/database/myemsl_metadata-website_prefs.sqlite3
COPY tests/apache_conf/modules /etc/apache2/conf-enabled/
COPY tests/apache_conf/sites/myemsl-reporting.conf /etc/apache2/sites-available/
RUN ln -s /etc/apache2/sites-available/myemsl-reporting.conf /etc/apache2/sites-enabled/
COPY config_files/general.ini /etc/myemsl/
RUN ln -s /var/www/html/application/resources /var/www/html/project_resources
RUN cp -f /usr/share/php5/php.ini-development /usr/local/etc/php/php.ini
RUN ln -s /usr/share/php5/pgsql/* /usr/local/etc/php/conf.d/
RUN ln -s /usr/lib/php5/20131226/p* /usr/local/lib/php/extensions/no-debug-non-zts-20131226/
RUN rm -rf /etc/apache2/sites-enabled/000-default.conf
RUN echo 'date.timezone = America/Los_Angeles' | tee "/usr/local/etc/php/conf.d/timezone.ini"
RUN ["/bin/bash", "-c", "source /etc/apache2/envvars"]
COPY resources /var/www/html/resources
COPY application /var/www/html/application

RUN chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" /var/www/html

CMD ["apache2-foreground"]
