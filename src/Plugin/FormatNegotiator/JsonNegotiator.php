<?php

namespace Drupal\relaxed\Plugin\FormatNegotiator;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

/**
 * @FormatNegotiator(
 *   id = "json",
 *   label = "JSON (default)",
 *   formats = {"json","mixed","related"}
 * )
 */
class JsonNegotiator extends NegotiatorBase implements ContainerFactoryPluginInterface {

  /**
   * JsonNegotiator constructor.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Serializer $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('replication.serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies($format, $method, $type) {
    return TRUE;
  }

}
