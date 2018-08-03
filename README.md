CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Relaxed module provides a RESTful API that exposes all content entities over
UUID endpoints. Utility endpoints are also provided for handling content
replication and subscribing to content changes.

 * For a full description of the module visit:
   https://www.drupal.org/project/relaxed

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/relaxed


REQUIREMENTS
------------

This module requires:

 * [Multiversion](https://www.drupal.org/project/multiversion)
 * [Replication](https://www.drupal.org/project/replication)


RECOMMENDED MODULES
-------------------

 * [Deploy](https://www.drupal.org/project/deploy)


INSTALLATION
------------

Install the Relaxed module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.

If you have not installed RELAXed Web Services with composer you will need to
run:

 * composer require relaxedws/replicator:dev-master

in your Drupal root directory, or use Composer Manager, to make sure all
dependencies are added.


CONFIGURATION
-------------
    1. Enable the Relaxed module at Admin > Extend.
    2. Enter the username and password credentials for a specified local user
       account for content migrations at Admin > Config > Web Services > Relaxed
       settings. An administrator account _may_ be used as your replicator
       account, it is recommended to use a restricted user account with the role
       "Replicator", exposed at Admin > People > User > Roles. Additionally, you
       may change the API root path to your desired path.
    3. Add a new RELAXed remote at Admin > Config > Web Services > Relaxed
       remotes.

More configuration info see [here](https://www.drupal.org/docs/8/modules/deploy/drupal-to-drupal-deployment-between-two-or-more-sites).

Use Case: Decoupled, offline-capable front-end sites
Since the API is heavily inspired by CouchDB (http://docs.couchdb.org/) it can
be used to create "offline-first" apps or websites with compatible frontend
libraries such as:

 * [PouchDB](http://pouchdb.com)
 * [Hood.ie](http://hood.ie)


MAINTAINERS
-----------

 * Andrei Jechiu ([jeqq](https://www.drupal.org/u/jeqq))
 * Tim Millwood ([timmillwood](https://www.drupal.org/u/timmillwood))
 * Damian Lee ([damiankloip](https://www.drupal.org/u/damiankloip))
 * Dick Olsson ([dixon_](https://www.drupal.org/u/dixon_))
