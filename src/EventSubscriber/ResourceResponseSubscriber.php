<?php

namespace Drupal\relaxed\EventSubscriber;

use Drupal\rest\ResourceResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\rest\EventSubscriber\ResourceResponseSubscriber as CoreResourceResponseSubscriber;

class ResourceResponseSubscriber extends CoreResourceResponseSubscriber {

  /**
   * Serializes ResourceResponse relaxed response's data.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof ResourceResponseInterface) {
      return;
    }

    if ($this->isRelaxedRoute()) {
      $event->setResponse($this->flattenResponse($response));
    }
  }

  protected function isRelaxedRoute() {
    return (substr($this->routeMatch->getRouteObject()->getDefault('_rest_resource_config'), 0, strlen('relaxed')) === 'relaxed');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run shortly before \Drupal\rest\EventSubscriber\ResourceResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 129];
    return $events;
  }

}
