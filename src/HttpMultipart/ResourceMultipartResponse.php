<?php

/**
 * @file
 * Definition of Drupal\relaxed\ResourceMultipartResponse.
 */

namespace Drupal\relaxed\HttpMultipart;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains data for serialization before sending the response.
 */
class ResourceMultipartResponse extends MultipartResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request)
  {
    // Fix the timeout error on replication.
    $this->headers->set('Connection', 'close');

    return parent::prepare($request);
  }

}
