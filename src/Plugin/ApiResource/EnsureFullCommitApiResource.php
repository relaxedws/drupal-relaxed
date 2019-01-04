<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\relaxed\Event\RelaxedEnsureFullCommitEvent;
use Drupal\relaxed\Event\RelaxedEvents;
use Drupal\relaxed\Http\ApiResourceResponse;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This resource does not do anything since Drupal does not (yet) have a concept
 * of transactions across multiple requests. This resource only exists to comply
 * with the replication protocol.
 *
 * @ApiResource(
 *   id = "ensure_full_commit",
 *   label = "Ensure Full Commit",
 *   path = "/{db}/_ensure_full_commit",
 * )
 */
class EnsureFullCommitApiResource extends ApiResourceBase {

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The CSS Collection Optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The JS Collection Optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS Collection Optimizer.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JS Collection Optimizer.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('event_dispatcher')
    );
  }

  public function post($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new BadRequestHttpException(t('Workspace does not exist'));
    }

    $response_data = [
      'ok' => TRUE,
      'instance_start_time' => (string) $workspace->created->value,
    ];

    // Flush all persistent caches.
    // This is executed based on old/previously known information, which is
    // sufficient, since new extensions cannot have any primed caches yet.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }

    // Flush asset file caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    _drupal_flush_css_js();

    $this->eventDispatcher->dispatch(RelaxedEvents::REPLICATION_ENSURE_FULL_COMMIT, new RelaxedEnsureFullCommitEvent($workspace));

    return new ApiResourceResponse($response_data, 201);
  }
}
