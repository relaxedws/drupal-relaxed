<?php

namespace Drupal\relaxed\HttpMultipart\HttpFoundation;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultipartResponse extends ResourceResponse {
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
   */
  public function __construct(array $parts = NULL, $status = 200, $headers = [], $subtype = NULL) {
    parent::__construct(NULL, $status, $headers);

    $this->subtype = $subtype ?: 'mixed';
    $this->boundary = md5(microtime());

    if (NULL !== $parts) {
      $this->setParts($parts);
      $content = '';
      foreach ($this->getParts() as $part) {
        $content .= "--{$this->boundary}\r\n";
        $content .= "Content-Type: {$part->headers->get('Content-Type')}\r\n\r\n";
        $content .= \Drupal::service('serializer')
          ->serialize($part->getResponseData(), 'json');
        $content .= "\r\n";
      }
      $content .= "--{$this->boundary}--";
      $this->setContent($content);
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
   * @param Response $part A response object to be part of the multipart response.
   *
   * @return MultipartResponse
   */
  public function setPart(Response $part) {
    $this->parts[] = $part;

    return $this;
  }

  /**
   * Sets multiple parts of the multipart response.
   *
   * @param Response[] $parts Response objects to be part of the multipart response.
   *
   * @return MultipartResponse
   */
  public function setParts(array $parts) {
    foreach ($parts as $part) {
      $this->setPart($part);
    }
    return $this;
  }

  /**
   * Returns the parts.
   *
   * @return Response[]
   */
  public function getParts() {
    return $this->parts;
  }

}
