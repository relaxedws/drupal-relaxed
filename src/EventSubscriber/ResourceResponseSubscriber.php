<?php

namespace Drupal\relaxed\EventSubscriber;

use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\rest\ResourceResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
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

    if ($this->isRelaxedRoute()) {
      $event->setResponse($this->flattenResponse($response));
    }
  }

  protected function isRelaxedRoute() {
    return (substr($this->routeMatch->getRouteObject()->getDefault('_rest_resource_config'), 0, strlen('relaxed')) === 'relaxed');
  }

  protected function isAttachment() {
    return (substr($this->routeMatch->getRouteObject()->getDefault('_rest_resource_config'), -strlen('attachment')) === 'attachment');
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  protected function getParameters(Request $request) {
    $parameters = array();
    foreach ($request->attributes->get('_route_params') as $key => $parameter) {
      // We don't want private parameters.
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run shortly before \Drupal\rest\EventSubscriber\ResourceResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 6];
    return $events;
  }

}
