<?php

/**
 * @file
 * Definition of Drupal\relaxed\ResourceMultipartResponse.
 */

namespace Drupal\relaxed\HttpMultipart;

use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains data for serialization before sending the response.
 */
class ResourceMultipartResponse extends MultipartResponse {

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    // Fix the timeout error on replication.
    $this->headers->set('Connection', 'close');

    return parent::prepare($request);
  }
}
