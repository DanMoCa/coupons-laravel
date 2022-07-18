FROM nginx:stable-alpine

ADD conf.d/default.prod.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/www/html

ADD . /var/www/html

RUN mkdir -p /etc/nginx/certs/mkcert

ADD certs /etc/nginx/certs/mkcert

