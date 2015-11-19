var getJSON = function(url, successHandler, errorHandler) {
  var xhr = new XMLHttpRequest();
  xhr.open('get', url, true);
  xhr.responseType = 'txt';
  xhr.onload = function() {
    var status = xhr.status;
    if (status == 200) {
      successHandler && successHandler(xhr.response);
    } else {
      errorHandler && errorHandler(status);
    }
  };
  xhr.send();
};

// Enable debugging mode.
PouchDB.debug.enable('*');
//PouchDB.debug.enable('pouchdb:api');
//PouchDB.debug.enable('pouchdb:http');

describe('Test replication', function () {

  it('Test basic push replication', function (done) {
    var db = new PouchDB('pouch_to_drupal');
    var remote = new PouchDB('http://replicator:replicator@localhost:8080/relaxed/default');
    getJSON('http://localhost:8080/documents.txt', function(data) {
      var lines = data.split(/\r\n|\n/);
      // Create an array with all docs.
      for(var line = 0; line < lines.length; line++) {
        if (lines[line]) {
          docs.push(JSON.parse(lines[line]));
        }
      }

      db.bulkDocs({ docs: docs }, {}, function (err, results) {
        db.replicate.to(remote, function (err, result) {
          result.ok.should.equal(true);
          result.docs_written.should.equal(docs.length);
          db.info(function (err, info) {
            console.log(err);
            verifyInfo(info, {
              update_seq: 12,
              doc_count: 12
            });
            done();
          });
        });
      });

    }, function(status) {
      console.log('Something went wrong.');
    });
  });

  it('Test basic pull replication', function (done) {
    var db = new PouchDB('drupal_to_pouch');
    var remote = new PouchDB('http://replicator:replicator@localhost:8080/relaxed/default');
    db.replicate.from(remote, {}, function (err, result) {
      result.ok.should.equal(true);
      result.docs_written.should.equal(14);
      db.info(function (err, info) {
        verifyInfo(info, {
          update_seq: 14,
          doc_count: 14
        });
        done();
      });
    });
  });
});

function verifyInfo(info, expected) {
  if (!testUtils.isCouchMaster()) {
    info.update_seq.should.equal(expected.update_seq, 'update_seq');
  }
  info.doc_count.should.equal(expected.doc_count, 'doc_count');
}
