<?php

namespace Drupal\relaxed\Plugin\FormatNegotiator;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

/**
 * @FormatNegotiator(
 *   id = "stream",
 *   label = "Stream / Base64 Stream",
 *   formats = {"stream","base64_stream"},
 *   priority = 10
 * )
 */
class StreamNegotiator extends NegotiatorBase implements ContainerFactoryPluginInterface {

  /**
   * StreamNegotiator constructor.
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

}
