<?php

namespace Drupal\relaxed\StackMiddleware;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Sets the format on requests to Relaxed routes.
 *
 * @internal
 */
class RequestFormatSetter implements HttpKernelInterface {

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * @var string
   */
  protected $api_root;

  /**
   * RequestFormatSetter constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
    $this->httpKernel = $http_kernel;
    $config = $config_factory->get('relaxed.settings');
    $this->api_root = trim($config->get('api_root'), '/');
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($this->isRelaxedRequest($request)) {
      $request->setRequestFormat(static::getFormat($request));
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Checks if the current request is a Relaxed request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  protected function isRelaxedRequest(Request $request) {
    return !empty($this->api_root) ? strpos($request->getPathInfo(), "/$this->api_root") === 0 : FALSE;
  }

  /**
   * Detects the format of the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   */
  protected function getFormat(Request $request) {
     // Check the format from the 'Accept' header.
    count(array_filter($request->getAcceptableContentTypes(), function ($accept) {
      if (strpos($accept, 'application/json') === 0) {
        return 'json';
      }
      elseif (strpos($accept, 'multipart/mixed') === 0) {
        return 'mixed';
      }
      elseif (strpos($accept, 'multipart/related') === 0) {
        return 'related';
      }
    }));

    // Default format is json.
    return 'json';
  }

}
