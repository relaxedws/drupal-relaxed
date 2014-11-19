<?php

/**
 * @file
 * Contains \Drupal\relaxed\EventSubscriber\RelaxedSubscriber.
 */

namespace Drupal\relaxed\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the kernel request event to add the 'multipart/mixed' media type.
 *
 * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
 *   The event to process.
 */
class RelaxedSubscriber implements EventSubscriberInterface {

  /**
   * Registers the 'mixed' format with the Request class.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->setFormat('mixed', 'multipart/mixed');
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents(){
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 50);
    return $events;
  }

}
