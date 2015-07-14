var docs = [
  {"_id":"1111b3b1-6d76-4813-9e12-18d4e91e1111","@type":"entity_test","_rev":"1-1111bb67431dd59bf85bdd6d6ff81111","id":[{"value":"1"}],"uuid":[{"value":"1111b3b1-6d76-4813-9e12-18d4e91e1111"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 1"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"1"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":1,"ids":["1111bb67431dd59bf85bdd6d6ff81111"]}},
  {"_id":"2222b3b1-6d76-4813-9e12-18d4e91e2222","@type":"entity_test","_rev":"2-2222bb67431dd59bf85bdd6d6ff82222","id":[{"value":"2"}],"uuid":[{"value":"2222b3b1-6d76-4813-9e12-18d4e91e2222"}],"langcode":[{"value":"en"}],"name":[{"value":"Entity name 2"}],"type":[{"value":"entity_test"}],"user_id":[{"target_id":"1"}],"revision_id":[{"value":"2"}],"field_test_text":[{"value":null,"format":null}],"workspace":[{"target_id":"default"}],"_revisions":{"start":2,"ids":["2222bb67431dd59bf85bdd6d6ff82222","2229807df77d61db1b9efd3742965730"]}},
  {"_id":"7de55da4-c168-4db9-b703-3fa1deacec3a","_rev":"1-bf269db660bf7ff795a03702fab9cb45","@context":{"taxonomy_term":"http://drupal8dev.loc/rest/type/taxonomy_term/tags"},"@id":"http://drupal8dev.loc/taxonomy/term/1","@type":"taxonomy_term","tid":[{"value":"1"}],"uuid":[{"value":"7de55da4-c168-4db9-b703-3fa1deacec3a"}],"vid":[{"target_id":"tags"}],"langcode":[{"value":"en"}],"name":[{"value":"Taxonomy term 1"}],"description":[{"value":null,"format":null}],"weight":[{"value":"0"}],"parent":[],"changed":[{"value":"1429256888"}],"default_langcode":[{"value":"1"}],"revision_id":[{"value":"1"}],"workspace":[{"target_id":"default"}],"path":[]},
  {"_id":"95cb0596-252b-433b-ab08-8bd799a2485b","_rev":"1-1f9e6df8144fce6beda130ec9fbd77cd","@context":{"taxonomy_term":"http://drupal8dev.loc/rest/type/taxonomy_term/tags"},"@id":"http://drupal8dev.loc/taxonomy/term/4","@type":"taxonomy_term","tid":[{"value":"4"}],"uuid":[{"value":"95cb0596-252b-433b-ab08-8bd799a2485b"}],"vid":[{"target_id":"tags"}],"langcode":[{"value":"en"}],"name":[{"value":"Taxonomy term 4"}],"description":[{"value":null,"format":null}],"weight":[{"value":"0"}],"parent":[],"changed":[{"value":"1429258005"}],"default_langcode":[{"value":"1"}],"revision_id":[{"value":"4"}],"workspace":[{"target_id":"default"}],"path":[]},
  {"_id":"e1227264-a62b-4f8f-a300-cf56520a8cab","_rev":"1-f506f473548a64af5ef42070370522ff","@context":{"taxonomy_term":"http://drupal8dev.loc/rest/type/taxonomy_term/tags"},"@id":"http://drupal8dev.loc/taxonomy/term/3","@type":"taxonomy_term","tid":[{"value":"3"}],"uuid":[{"value":"e1227264-a62b-4f8f-a300-cf56520a8cab"}],"vid":[{"target_id":"tags"}],"langcode":[{"value":"en"}],"name":[{"value":"Taxonomy term 3"}],"description":[{"value":null,"format":null}],"weight":[{"value":"0"}],"parent":[],"changed":[{"value":"1429258005"}],"default_langcode":[{"value":"1"}],"revision_id":[{"value":"3"}],"workspace":[{"target_id":"default"}],"path":[]},
  {"_id":"f05607d1-0a60-4f1e-bc30-48e4eadca8b1","_rev":"1-702a7687f3f029d44163f7b3cda805b5","@context":{"taxonomy_term":"http://drupal8dev.loc/rest/type/taxonomy_term/tags"},"@id":"http://drupal8dev.loc/taxonomy/term/2","@type":"taxonomy_term","tid":[{"value":"2"}],"uuid":[{"value":"f05607d1-0a60-4f1e-bc30-48e4eadca8b1"}],"vid":[{"target_id":"tags"}],"langcode":[{"value":"en"}],"name":[{"value":"Taxonomy term 2"}],"description":[{"value":null,"format":null}],"weight":[{"value":"0"}],"parent":[],"changed":[{"value":"1429256888"}],"default_langcode":[{"value":"1"}],"revision_id":[{"value":"2"}],"workspace":[{"target_id":"default"}],"path":[]},
  {"_id":"86db661d-ad81-42c0-8605-5a7de8b5a677","_rev":"2-6af4f08a208c429366602076b1744669","@context":{"node":"http://drupal8dev.loc/rest/type/node/article"},"@id":"http://drupal8dev.loc/node/1","@type":"node","nid":[{"value":"1"}],"uuid":[{"value":"86db661d-ad81-42c0-8605-5a7de8b5a677"}],"vid":[{"value":"2"}],"type":[{"target_id":"article"}],"langcode":[{"value":"en"}],"title":[{"value":"Article 1"}],"uid":[{"target_id":"1"}],"status":[{"value":"1"}],"created":[{"value":"1429256714"}],"changed":[{"value":"1429256888"}],"promote":[{"value":"1"}],"sticky":[{"value":"0"}],"revision_timestamp":[{"value":"1429256714"}],"revision_uid":[{"target_id":"1"}],"revision_log":[{"value":""}],"default_langcode":[{"value":"1"}],"workspace":[{"target_id":"default"}],"path":[],"body":[{"value":"<p>Lorem ipsum</p>\r\n","format":"basic_html","summary":""}],"comment":[{"status":"2","cid":null,"last_comment_timestamp":null,"last_comment_name":null,"last_comment_uid":null,"comment_count":null}],"field_tags":[{"target_id":"1"},{"target_id":"2"}]},
  {"_id":"5b00cd62-2695-481d-9a04-4d3868389307","_rev":"1-7f080bc0a77c758dfff9c8035c576fc7","@context":{"node":"http://drupal8dev.loc/rest/type/node/page"},"@id":"http://drupal8dev.loc/node/2","@type":"node","nid":[{"value":"2"}],"uuid":[{"value":"5b00cd62-2695-481d-9a04-4d3868389307"}],"vid":[{"value":"3"}],"type":[{"target_id":"page"}],"langcode":[{"value":"en"}],"title":[{"value":"Basic page 1"}],"uid":[{"target_id":"1"}],"status":[{"value":"1"}],"created":[{"value":"1429257969"}],"changed":[{"value":"1429257979"}],"promote":[{"value":"0"}],"sticky":[{"value":"0"}],"revision_timestamp":[{"value":"1429257979"}],"revision_uid":[{"target_id":"1"}],"revision_log":[{"value":""}],"default_langcode":[{"value":"1"}],"workspace":[{"target_id":"default"}],"path":[],"body":[{"value":"<p>Lorem ipsum.</p>\r\n","format":"basic_html","summary":""}]},
  {"_id":"b5150cb2-8a09-413a-a91f-315f70f9b68c","_rev":"1-28ca2de82a59305f74d1de98b0a93837","@context":{"node":"http://drupal8dev.loc/rest/type/node/article"},"@id":"http://drupal8dev.loc/node/3","@type":"node","nid":[{"value":"3"}],"uuid":[{"value":"b5150cb2-8a09-413a-a91f-315f70f9b68c"}],"vid":[{"value":"4"}],"type":[{"target_id":"article"}],"langcode":[{"value":"en"}],"title":[{"value":"Article 2"}],"uid":[{"target_id":"1"}],"status":[{"value":"1"}],"created":[{"value":"1429257991"}],"changed":[{"value":"1429258005"}],"promote":[{"value":"1"}],"sticky":[{"value":"0"}],"revision_timestamp":[{"value":"1429257991"}],"revision_uid":[{"target_id":"1"}],"revision_log":[{"value":""}],"default_langcode":[{"value":"1"}],"workspace":[{"target_id":"default"}],"path":[],"body":[{"value":"<p>Lorem ipsum</p>\r\n","format":"basic_html","summary":""}],"comment":[{"status":"2","cid":null,"last_comment_timestamp":null,"last_comment_name":null,"last_comment_uid":null,"comment_count":null}],"field_tags":[{"target_id":"3"},{"target_id":"4"}]},
];

// Enable debugging mode.
//PouchDB.debug.enable('*');
//PouchDB.debug.enable('pouchdb:api');
//PouchDB.debug.enable('pouchdb:http');

describe('Test replication', function () {

  it('Test basic push replication', function (done) {
    var db = new PouchDB('pouch_to_drupal');
    var remote = new PouchDB('http://replicator:replicator@drupal.loc/relaxed/default');
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
    var remote = new PouchDB('http://replicator:replicator@drupal.loc/relaxed/default');
    //remote.bulkDocs({ docs: docs }, {}, function (err, results) {
      db.replicate.from(remote, {}, function (err, result) {
        result.ok.should.equal(true);
        result.docs_written.should.equal(12);
        db.info(function (err, info) {
          verifyInfo(info, {
            update_seq: 12,
            doc_count: 12
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
