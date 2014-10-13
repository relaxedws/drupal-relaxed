<?php

namespace Drupal\relaxed\Encoder;

use Drupal\Component\Utility\Random;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class FileEncoder implements EncoderInterface, DecoderInterface {

  /**
   * @var array
   * @todo Make this dynamic.
   */
  protected $formats = array('txt', 'png');

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = array()) {
    return file_get_contents($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()) {
    $generator = new Random();
    $uri = 'temporary://' . $generator->name();
    file_put_contents($uri, $data);
    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, $this->formats);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return in_array($format, $this->formats);
  }
}
