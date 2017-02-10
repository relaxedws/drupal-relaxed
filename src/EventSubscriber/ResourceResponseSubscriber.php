<?php

namespace Drupal\relaxed\EventSubscriber;

use Drupal\rest\ResourceResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\rest\EventSubscriber\ResourceResponseSubscriber as CoreResourceResponseSubscriber;

class ResourceResponseSubscriber extends CoreResourceResponseSubscriber {

  /**
   * Serializes ResourceResponse relaxed responses' data.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof ResourceResponseInterface) {
      return;
    }


    $request = $event->getRequest();
    if (!$format = $this->getResponseFormat($this->routeMatch, $request) && $this->isRelaxedRoute()) {
      $this->renderResponseBody($request, $response, $this->serializer, 'json');
      $event->setResponse($this->flattenResponse($response));
    }
  }


  protected function isRelaxedRoute() {
    return (substr($this->routeMatch->getRouteName(), -strlen('rest.relaxed.')) === 'rest.relaxed.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 6];
    return $events;
  }

}
