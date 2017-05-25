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
    $events[KernelEvents::RESPONSE][] = ['onResponse', static::determineOnResponsePriority()];
    return $events;
  }

  /**
   * Determines correct response priority based on the Drupal minor version.
   *
   * @return int
   */
  public static function determineOnResponsePriority() {
    // Get the minor version only from the \Drupal::VERSION string.
    $minor_version = substr(\Drupal::VERSION, 0, 3);

    // In versions before 8.4 the rest ResourceResponseSubscriber had a
    // priority of 5. In https://www.drupal.org/node/2827797 it got
    // increased to 128. So 8.4 needs to have a priority higher than that.
    if (version_compare($minor_version, '8.4', '<')) {
      return 6;
    }

    return 129;
  }

}
