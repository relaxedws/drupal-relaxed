<?php

/**
 * @file
 * Definition of Drupal\relaxed\ResourceMultipartResponse.
 */

namespace Drupal\relaxed\HttpMultipart;

use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;

/**
 * Contains data for serialization before sending the response.
 *
 * We do not want to abuse the $content property on the Response class to store
 * our response data. $content implies that the provided data must either be a
 * string or an object with a __toString() method, which is not a requirement
 * for data used here.
 */
class ResourceMultipartResponse extends MultipartResponse {

  /**
   * Returns response data that should be serialized.
   *
   * @return mixed
   *   Response data that should be serialized.
   */
  public function getParts() {
    return $this->parts;
  }
}
