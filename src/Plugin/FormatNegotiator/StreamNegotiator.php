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
      // @todo Inject a different serializer for stream stuff only.
      $container->get('replication.serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies($format, $method, $type) {
    // If the format doesn't match at all, we don't care anyway.
    if (!parent::applies($format, $method, $type)) {
      return FALSE;
    }

    // If it's applying to response data, only allow stream for GET and HEAD.
    if ($type === 'response') {
      return in_array($method, ['get', 'head'], TRUE);
    }

    // If this is for applying to incoming request data, it's ok.
    // I.e. '$type === "request"'.
    return TRUE;
  }

}
