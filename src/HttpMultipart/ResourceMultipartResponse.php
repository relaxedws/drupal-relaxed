<?php

namespace Drupal\relaxed\HttpMultipart;

use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * Sends content for the current web response.
   */
  public function sendContent() {
    // This fixes the "Malformed encoding found in chunked-encoding"
    // error message in curl and makes possible to get the correct response body.
    $size = strlen($this->getContent());
    echo "$size\r\n";

    parent::sendContent();
  }

}
