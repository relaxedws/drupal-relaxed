<?php

namespace Drupal\relaxed\Plugin\FormatNegotiator;


use Drupal\Core\Plugin\PluginBase;
use Drupal\relaxed\Plugin\FormatNegotiatorInterface;

abstract class NegotiatorBase extends PluginBase implements FormatNegotiatorInterface {

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  public function serializer() {
    return $this->serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($format, $method, $type) {
    return in_array($format, $this->getPluginDefinition()['formats']);
  }

}
