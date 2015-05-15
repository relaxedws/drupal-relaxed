<?php

namespace Drupal\relaxed\HttpMultipart\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultipartResponse extends Response {
  /**
   * @var string
   */
  public $subtype;

  /**
   * @var string
   */
  public $boundary;

  /**
   * @var Response[]
   */
  protected $parts;

  /**
   * Constructor.
   *
   * @param array|NULL $parts
   *   The multiparts for the response.
   * @param int $status
   *   The HTML status code.
   * @param array $headers
   *   The response headers.
   * @param string $subtype
   *   The subtype.
   */
  public function __construct(array $parts = NULL, $status = 200, $headers = array(), $subtype = NULL) {
    parent::__construct(NULL, $status, $headers);

    $this->subtype = $subtype ?: 'mixed';
    $this->boundary = md5(microtime());

    if ($parts !== NULL) {
      $this->setParts($parts);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    $this->headers->set('Content-Type', "multipart/{$this->subtype}; boundary=\"{$this->boundary}\"");
    $this->headers->set('Transfer-Encoding', 'chunked');

    return parent::prepare($request);
  }

  /**
   * Sets a part of the multipart response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $part
   *   A response object to be part of the multipart response.
   *
   * @return $this
   */
  public function setPart(Response $part) {
    $this->parts[] = $part;

    return $this;
  }

  /**
   * Sets multiple parts of the multipart response.
   *
   * @param \Symfony\Component\HttpFoundation\Response[] $parts
   *   Response objects to be part of the multipart response.
   *
   * @return $this
   */
  public function setParts(array $parts) {
    foreach ($parts as $part) {
      $this->setPart($part);
    }
    return $this;
  }

  /**
   * Get the repsonse parts.
   *
   * @return \Symfony\Component\HttpFoundation\Response[]
   *  List of response parts.
   */
  public function getParts() {
    return $this->parts;
  }

  /**
   * Sends content for the current web response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The repsonse object.
   */
  public function sendContent() {
    $content = '';
    foreach ($this->parts as $part) {
      $content .= "--{$this->boundary}\r\n";
      $content .= "{$part->headers}\r\n";
      $content .= $part->getContent();
      $content .= "\r\n";
    }
    $content .= "--{$this->boundary}--";
    // Finally send all the content.
    echo strlen($content) . "\r\n" . $content;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException when the content is not null
   */
  public function setContent($content) {
    if ($content !== NULL) {
      throw new \LogicException('The content cannot be set on a MultipartResponse instance.');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return FALSE
   */
  public function getContent() {
    return FALSE;
  }
}
