<?php

namespace Drupal\relaxed\Plugin;

/**
 * Contract for Api resource route generator.
 */
interface ApiResourceRouteGeneratorInterface {

  /**
   * @param ApiResourceInterface $api_resource
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes(ApiResourceInterface $api_resource);

}
