<?php

namespace Drupal\relaxed\Plugin\FormatNegotiator;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\relaxed\Plugin\FormatNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

/**
 * @FormatNegotiator(
 *   id = "default",
 *   label = "JSON (default)",
 *   formats = {"json"}
 * )
 */
class DefaultNegotiator implements FormatNegotiatorInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * DefaultNegotiator constructor.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   */
  public function __construct(Serializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('replication.serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies($format, $method) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function serializer() {
    return $this->serializer;
  }

}
