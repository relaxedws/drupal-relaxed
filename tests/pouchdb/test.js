var request = require('request');
var docsInfo = '';
request('http://localhost:8080/modules/relaxed/tests/fixtures/documents.txt', function (error, response, body) {
  if (!error && response.statusCode == 200) {
    docsInfo = body;
  }
});
var docs = docsInfo.split(/\r\n|\n/);

// Enable debugging mode.
PouchDB.debug.enable('*');
//PouchDB.debug.enable('pouchdb:api');
//PouchDB.debug.enable('pouchdb:http');

describe('Test replication', function () {

  it('Test basic push replication', function (done) {
    var db = new PouchDB('pouch_to_drupal');
    var remote = new PouchDB('http://replicator:replicator@localhost:8080/relaxed/default');
    db.bulkDocs({ docs: docs }, {}, function (err, results) {
      db.replicate.to(remote, function (err, result) {
        result.ok.should.equal(true);
        result.docs_written.should.equal(docs.length);
        db.info(function (err, info) {
          console.log(err);
          verifyInfo(info, {
            update_seq: 9,
            doc_count: 9
          });
          done();
        });
      });
    });
  });

  it('Test basic pull replication', function (done) {
    var db = new PouchDB('drupal_to_pouch');
    var remote = new PouchDB('http://replicator:replicator@localhost:8080/relaxed/default');
    //remote.bulkDocs({ docs: docs }, {}, function (err, results) {
      db.replicate.from(remote, {}, function (err, result) {
        result.ok.should.equal(true);
        result.docs_written.should.equal(11);
        db.info(function (err, info) {
          verifyInfo(info, {
            update_seq: 11,
            doc_count: 11
          });
          done();
        });
      });
    //});
  });
});

function verifyInfo(info, expected) {
  if (!testUtils.isCouchMaster()) {
    info.update_seq.should.equal(expected.update_seq, 'update_seq');
  }
  info.doc_count.should.equal(expected.doc_count, 'doc_count');
}
