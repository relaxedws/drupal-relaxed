<?php

namespace Drupal\relaxed\HttpMultipart\HttpFoundation;

use Drupal\relaxed\Http\ApiResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultipartResponse extends ApiResourceResponse {
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
  protected $parts = [];

  /**
   * Constructor.
   */
  public function __construct(array $parts = NULL, $status = 200, $headers = [], $subtype = NULL) {
    parent::__construct(NULL, $status, $headers);

    if ($parts) {
      $this->setParts($parts);
    }

    $this->subtype = $subtype ?: 'mixed';
    $this->boundary = md5(microtime());
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    $this->headers->set('Content-Type', sprintf('multipart/%s; boundary="%s"', $this->subtype, $this->boundary));
    $this->headers->set('Transfer-Encoding', 'chunked');

    // Prepare the response content from the parts.
    $parts = $this->getParts();

    if ($parts) {
      $content = '';

      foreach ($this->getParts() as $part) {
        $content .= sprintf("--%s\r\n", $this->boundary);
        $content .= sprintf("Content-Type: %s\r\n\r\n", $part->headers->get('Content-Type'));
        $content .= $part->getContent();
        $content .= "\r\n";
      }

      $content .= sprintf("--%s--", $this->boundary);

      $this->setContent($content);
    }

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
