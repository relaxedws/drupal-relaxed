<?php

namespace Drupal\relaxed\HttpMultipart\Message;

use GuzzleHttp\Psr7;

class MultipartMessageFactory {

  /**
   * {@inheritdoc}
   */
  public function createResponse($statusCode, array $headers = [], $body = null, array $options = [])
  {
    if (null !== $body) {
      $body = $stream = Psr7\stream_for($body);
    }

    return new MultipartResponse($statusCode, $headers, $body, $options);
  }
}
