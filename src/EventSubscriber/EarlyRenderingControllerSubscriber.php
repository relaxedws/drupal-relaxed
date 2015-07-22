<?php

/**
 * @file
 * Contains \Drupal\relaxed\EventSubscriber\EarlyRenderingControllerSubscriber.
 */

namespace Drupal\relaxed\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\relaxed\Controller\ResourceController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber that wraps controllers, to handle early rendering.
 */
class EarlyRenderingControllerSubscriber implements EventSubscriberInterface {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EarlyRenderingControllerSubscriber instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, RendererInterface $renderer) {
    $this->controllerResolver = $controller_resolver;
    $this->renderer = $renderer;
  }

  /**
   * Ensures bubbleable metadata from early rendering is not lost.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The controller event.
   */
  public function onController(FilterControllerEvent $event) {
    $controller = $event->getController();

    // See \Symfony\Component\HttpKernel\HttpKernel::handleRaw().
    $arguments = $this->controllerResolver->getArguments($event->getRequest(), $controller);

    $event->setController(function() use ($controller, $arguments) {
      return $this->wrapControllerExecutionInRenderContext($controller, $arguments);
    });
  }

  /**
   * Wraps a controller execution in a render context.
   *
   * @param callable $controller
   *   The controller to execute.
   * @param array $arguments
   *   The arguments to pass to the controller.
   *
   * @return mixed
   *   The return value of the controller.
   *
   * @throws \LogicException
   *   When early rendering has occurred in a controller that returned a
   *   Response or domain object that cares about attachments or cacheability.
   *
   * @see \Symfony\Component\HttpKernel\HttpKernel::handleRaw()
   */
  protected function wrapControllerExecutionInRenderContext($controller, array $arguments) {
    $context = new RenderContext();

    $response = $this->renderer->executeInRenderContext($context, function() use ($controller, $arguments) {
      // Call the actual controller.
      return call_user_func_array($controller, $arguments);
    });

    // If early rendering happened, i.e. if code in the controller called
    // drupal_render() outside of a render context, then the bubbleable metadata
    // for that is stored in the current render context.
    if (!$context->isEmpty() && $controller[0] instanceof ResourceController) {
      /** @var \Drupal\Core\Render\BubbleableMetadata $early_rendering_bubbleable_metadata */
      $early_rendering_bubbleable_metadata = $context->pop();

      // @todo Review this.
      // @see \Drupal\Core\EventSubscriber\EarlyRenderingControllerWrapperSubscriber
      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($early_rendering_bubbleable_metadata);
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER][] = ['onController', 100];

    return $events;
  }

}
