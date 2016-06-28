<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\replication\Changes\ChangesInterface;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\replication\Changes\Changes",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   },
 *   no_cache = TRUE
 * )
 */
class ChangesResource extends ResourceBase {

  /** @var ChangesFactoryInterface  */
  protected $changesFactory;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\replication\ChangesFactoryInterface $changes_factory
   *  The ChangesFactory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ChangesFactoryInterface $changes_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->changesFactory = $changes_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('replication.changes_factory')
    );
  }

  public function get($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }

    /** @var ChangesInterface $changes */
    $changes = $this->changesFactory->get($workspace);

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $changes->includeDocs(TRUE);
    }

    return new ResourceResponse($changes, 200);
  }

}
