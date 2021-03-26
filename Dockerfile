FROM elephantbox/alpine-php-node:latest
COPY . /app
RUN cp /app/demo/composer.json.dist /app/demo/composer.json
WORKDIR /app
RUN npm install
WORKDIR /app/demo
RUN composer install