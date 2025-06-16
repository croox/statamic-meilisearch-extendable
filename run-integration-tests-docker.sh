#!/bin/bash
set -x
set -euo pipefail

NETWORK_NAME="croox_statamic_meilisearch_extendable"
MEILISEARCH_CONTAINER_NAME="croox_statamic_meilisearch_extendable_meilisearch"
PHP_CONTAINER_NAME="croox_statamic_meilisearch_extendable_php"
MEILISEARCH_KEY="__meilisearch_key__"

# cleanup, if previous run was interrupted
docker stop $MEILISEARCH_CONTAINER_NAME || true;
docker rm -f $MEILISEARCH_CONTAINER_NAME || true;
docker network rm -f $NETWORK_NAME;

docker network create $NETWORK_NAME;

docker run -d \
  --rm \
  --name $MEILISEARCH_CONTAINER_NAME \
  --network $NETWORK_NAME \
  -e MEILI_MASTER_KEY=$MEILISEARCH_KEY \
  getmeili/meilisearch:v1.15;

docker run \
    --rm \
    --name $PHP_CONTAINER_NAME \
    --network $NETWORK_NAME \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    -e MEILISEARCH_URL=http://$MEILISEARCH_CONTAINER_NAME:7700 \
    -e MEILISEARCH_KEY=$MEILISEARCH_KEY \
    -it \
    php:8.3-cli /var/www/html/vendor/bin/phpunit --testsuite Integration;

docker stop $MEILISEARCH_CONTAINER_NAME;
docker rm -f $MEILISEARCH_CONTAINER_NAME;
docker network rm -f $NETWORK_NAME;
