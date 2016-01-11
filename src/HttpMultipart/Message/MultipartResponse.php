<?php

namespace Drupal\relaxed\HttpMultipart\Message;

use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response;

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
      $this->headers->remove('Content-Length');
      $this->headers->remove('Transfer-Encoding');
    } else {
      foreach (self::parseMultipartBody($body) as $parts) {
        $this->bodies[] = Psr7\stream_for($parts['body']);
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
        $message_parts = explode("\r\n\r\n", $message, 2);
        // In $message_parts we should have two values - headers ($message_parts[0])
        // and body ($message_parts[1]).
        $header_lines = $message_parts[0];
        $body = isset($message_parts[1]) ? $message_parts[1] : NULL;
        // Process the headers - transform the string in an associative array
        // where the keys are headers name and the values - headers value.
        foreach (explode("\r\n", $header_lines) as $header_line) {
          $header_parts = preg_split('/:\s+/', $header_line, 2);
          if (count($header_parts) == 2) {
            list($key, $value) = $header_parts;
            $headers[strtolower($key)] = $value;
          }
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
