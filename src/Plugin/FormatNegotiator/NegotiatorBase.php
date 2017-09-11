<?php

namespace Drupal\relaxed\Plugin\FormatNegotiator;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\PluginBase;
use Drupal\relaxed\Plugin\FormatNegotiatorInterface;

abstract class NegotiatorBase extends PluginBase implements FormatNegotiatorInterface {

  /**
   * @var array
   */
  protected $cacheTags = [];

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function serializer($format, $method, $type) {
    return $this->serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($format, $method, $type) {
    return in_array($format, $this->getPluginDefinition()['formats'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
