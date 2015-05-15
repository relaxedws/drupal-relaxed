<?php

namespace Drupal\relaxed\HttpMultipart\Message;

use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Stream\Stream;

class MultipartMessageFactory extends MessageFactory {

  /**
   * {@inheritdoc}
   */
  public function createResponse($statusCode, array $headers = [], $body = NULL, array $options = []) {
    if ($body !== NULL) {
      $body = Stream::factory($body);
    }

    return new MultipartResponse($statusCode, $headers, $body, $options);
  }
}
