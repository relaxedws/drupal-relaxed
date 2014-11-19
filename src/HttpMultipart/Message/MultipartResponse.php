<?php

namespace Drupal\relaxed\HttpMultipart\Message;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Stream\StreamInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo Patch Response and AbstractMessage to declare their properties as protected rather than private.
 */
class MultipartResponse extends Response
{
  /** @var StreamInterface[] */
  private $bodies = array();

  /**
   * {@inheritdoc}
   */
  public function setBody(StreamInterface $body = null)
  {
    if (null === $body) {
      $this->removeHeader('Content-Length');
      $this->removeHeader('Transfer-Encoding');
    } else {
      foreach (self::parseMultipartBody($body) as $parts) {
        $this->bodies[] = Stream::factory($parts['body']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBody()
  {
    return array_shift($this->bodies);
  }

  /**
   * Parses a multipart body into multiple parts.
   *
   * @param StreamInterface $stream
   *
   * @return array
   */
  public static function parseMultipartBody(StreamInterface $stream)
  {
    $parts = [];
    preg_match('/--(.*)\b/', $stream, $boundary);

    if (!empty($boundary)) {
      $messages = array_filter(array_map('trim', explode($boundary[0], $stream)));

      foreach ($messages as $message) {
        if ($message == '--') {
          break;
        }
        $headers = [];
        list($header_lines, $body) = explode("\r\n\r\n", $message, 2);
        foreach (explode("\r\n", $header_lines) as $header_line) {
          list($key, $value) = preg_split('/:\s+/', $header_line, 2);
          $headers[strtolower($key)] = $value;
        }
        $parts[] = [
          'headers' => $headers,
          'body' => $body,
        ];
      }
    }

    return $parts;
  }
}
