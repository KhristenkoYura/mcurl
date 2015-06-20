#!/bin/bash
apt-get install nginx
cp travis_nginx.conf /etc/nginx/nginx.conf
/etc/init.d/nginx restart