#!/bin/sh

if [ $COUCHDB_VERSION = "2.0-dev" ]; then
  docker run -d -p 3001:5984 klaemo/couchdb:$COUCHDB_VERSION --with-haproxy --with-admin-party-please -n 1
  export COUCH_PORT=3001
elif [ $COUCHDB_VERSION = "1.6.1" ]; then
  export COUCH_PORT=5984
else
  echo "Unknown CouchDB version."
fi

# Wait for couchdb to start, add CORS.
npm install add-cors-to-couchdb
while [ '200' != $(curl -s -o /dev/null -w %{http_code} http://127.0.0.1:${COUCH_PORT}) ]; do
  echo waiting for couch to load... ;
  sleep 1;
done
./node_modules/.bin/add-cors-to-couchdb http://127.0.0.1:${COUCH_PORT}
