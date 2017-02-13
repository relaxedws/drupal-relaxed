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


    $request = $event->getRequest();
    if ($this->isRelaxedRoute()) {
      $format = $this->getResponseFormat($this->routeMatch, $request);
      $format = (in_array($request->getMethod(), array('GET', 'HEAD')) && $format === 'stream') ? 'stream' : 'json';
      $this->renderResponseBody($request, $response, $this->serializer, $format);
      $event->setResponse($this->flattenResponse($response));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function renderResponseBody(Request $request, ResourceResponseInterface $response, SerializerInterface $serializer, $format) {
    $responses = ($response instanceof MultipartResponse) ? $response->getParts() : array($response);
    // @todo {@link https://www.drupal.org/node/2600500 Check if this is safe.}
    $query = $request->query->all();
    $resource_config_id = $this->routeMatch->getRouteObject()->getDefault('_rest_resource_config');
    $context = array('query' => $query, 'resource_id' => $resource_config_id);

    $render_contexts = [];
    foreach ($responses as $response_part) {
      if ($response_data = $response_part->getResponseData()) {
        // Collect bubbleable metadata in a render context.
        $render_context = new RenderContext();
        $response_output = $this->renderer->executeInRenderContext($render_context, function() use ($serializer, $response_data, $format, $context) {
          return $serializer->serialize($response_data, $format, $context);
        });
        if (!$render_context->isEmpty()) {
          $render_contexts[] = $render_context->pop();
        }
        $response_part->setContent($response_output);
      }
      if (!$response_part->headers->get('Content-Type')) {
        $response_part->headers->set('Content-Type', $request->getMimeType($format));
      }
    }

    if ($request->getMethod() !== 'HEAD') {
      $response->headers->set('Content-Length', strlen($response->getContent()));
    }
  }

  protected function isRelaxedRoute() {
    return (substr($this->routeMatch->getRouteObject()->getDefault('_rest_resource_config'), 0, strlen('relaxed')) === 'relaxed');
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
