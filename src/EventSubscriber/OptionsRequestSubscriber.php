<?php

/**
 * @file
 * Contains \Drupal\relaxed\EventSubscriber\OptionsRequestSubscriber.
 */

namespace Drupal\relaxed\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles options requests.
 *
 * Therefore it sends a options response using all methods on all possible
 * routes.
 * @todo Review/remove this when https://www.drupal.org/node/2237231 lands.
 */
class OptionsRequestSubscriber implements EventSubscriberInterface {

  /**
   * @var \Symfony\Cmf\Component\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Creates a new OptionsRequestSubscriber instance.
   *
   * @param \Symfony\Cmf\Component\Routing\RouteProviderInterface $routeProvider
   *   THe route provider.
   */
  public function __construct(RouteProviderInterface $routeProvider) {
    $this->routeProvider = $routeProvider;
  }

  /**
   * Tries to handle the options request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function onRequest(GetResponseEvent $event) {
    if ($event->getRequest()->isMethod('OPTIONS')) {
      $routes = $this->routeProvider->getRouteCollectionForRequest($event->getRequest());
      // In case we don't have any routes, a 403 should be thrown by the normal
      // request handling.
      $methods = [];
      if (count($routes) > 0) {
        $current_route_name = Url::createFromRequest($event->getRequest())->getRouteName();
        $current_route = $this->routeProvider->getRouteByName($current_route_name);
        foreach ($routes as $route) {
          if ($current_route->getPath() !== $route->getPath()) {
            continue;
          }
          $methods = array_merge($methods, $route->getMethods());
        }
        $response = new Response('', 200, ['Allow' => implode(', ', $methods)]);
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a high priority so its executed before routing.
    $events[KernelEvents::REQUEST][] = ['onRequest', 1000] ;
    return $events;
  }

}
