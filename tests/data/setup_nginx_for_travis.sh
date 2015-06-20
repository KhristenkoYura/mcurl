#!/bin/bash
apt-get install nginx
echo "127.0.0.1 test.multi.dev" >> /etc/hosts
cp tests/data/travis_nginx.conf /etc/nginx/nginx.conf-test
/etc/init.d/nginx restart