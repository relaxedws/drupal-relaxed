<?php

namespace Drupal\relaxed;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'relaxed_normalizer' and 'relaxed_encoder' to the Serializer.
 */
class RegisterSerializerCompilerPass implements CompilerPassInterface {

  /**
   * The replication serializer service name.
   */
  const SERIALIZER_SERVICE_NAME = 'relaxed.serializer';

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition(static::SERIALIZER_SERVICE_NAME);

    // Find all replication serialization formats known.
    $formats = [];
    $format_providers = [];

    // Retrieve registered Normalizers and Encoders from the container.
    foreach ($container->findTaggedServiceIds('relaxed_normalizer') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $normalizers[$priority][] = new Reference($id);
      $normalizers_id[$priority][] = $id;
    }

    foreach ($container->findTaggedServiceIds('relaxed_encoder') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $encoders[$priority][] = new Reference($id);

      // Collect formats and providers.
      $format = $attributes[0]['format'];
      $formats[] = $format;

      if ($provider_tag = $container->getDefinition($id)->getTag('_provider')) {
        $format_providers[$format] = $provider_tag[0]['provider'];
      }
    }

    // Add the registered Normalizers and Encoders to the Serializer.
    if (!empty($normalizers)) {
      $definition->replaceArgument(0, static::flattenAndSort($normalizers));
    }

    if (!empty($encoders)) {
      $definition->replaceArgument(1, static::flattenAndSort($encoders));
    }

    $container->setParameter(sprintf('%s.formats', static::SERIALIZER_SERVICE_NAME), $formats);
    $container->setParameter(sprintf('%s.format_providers', static::SERIALIZER_SERVICE_NAME), $format_providers);
  }

  /**
   * Flattens and sorts by priority.
   *
   * Order services from highest priority number to lowest (reverse sorting).
   *
   * @param array $services
   *   A nested array keyed on priority number. For each priority number, the
   *   value is an array of Symfony\Component\DependencyInjection\Reference
   *   objects, each a reference to a normalizer or encoder service.
   *
   * @return array
   *   A flattened array of Reference objects from $services, ordered from high
   *   to low priority.
   */
  protected static function flattenAndSort($services) {
    $sorted = [];
    krsort($services);

    // Flatten the array.
    foreach ($services as $a) {
      $sorted = array_merge($sorted, $a);
    }

    return $sorted;
  }

}
