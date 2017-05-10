#!/bin/sh

set -ev

if [ $COUCHDB_VERSION = "2.0-dev" ]; then
  docker run -d -p 5984:5984 klaemo/couchdb:$COUCHDB_VERSION
elif [ $COUCHDB_VERSION = "1.6.1" ]; then
  docker run -d -p 5984:5984 klaemo/couchdb:$COUCHDB_VERSION
else
  echo "Unknown CouchDB version."
fi

# Wait for couchdb to start, add CORS.
npm -g install npm@latest
npm config set strict-ssl false
npm install add-cors-to-couchdb
while [ '200' != $(curl -s -o /dev/null -w %{http_code} http://127.0.0.1:5984) ]; do
  echo waiting for couch to load... ;
  sleep 1;
done
./node_modules/.bin/add-cors-to-couchdb http://127.0.0.1:5984
