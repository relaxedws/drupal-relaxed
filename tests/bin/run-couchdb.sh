#!/bin/sh

COUCHDB_VERSION=2.3.1
docker run -d -p $COUCH_PORT:5984 apache/couchdb:$COUCHDB_VERSION --with-haproxy --with-admin-party-please -n 1

npm -g install npm@latest
# Wait for couchdb to start, add CORS.
npm install add-cors-to-couchdb
while [ '200' != $(curl -s -o /dev/null -w %{http_code} http://127.0.0.1:${COUCH_PORT}) ]; do
  echo waiting for couch to load... ;
  sleep 1;
done
./node_modules/.bin/add-cors-to-couchdb http://127.0.0.1:${COUCH_PORT}
