#!/bin/bash
apt-get install nginx
cp tests/data/travis_nginx.conf /etc/nginx/nginx.conf-test
/etc/init.d/nginx restart