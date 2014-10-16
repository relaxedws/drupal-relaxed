<?php

namespace Drupal\relaxed\Controller;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ResourceController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  protected $request;

  /**
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected function container() {
    return \Drupal::getContainer();
  }

  /**
   * @return \Symfony\Component\Serializer\SerializerInterface
   */
  protected function serializer() {
    if (!$this->serializer) {
      $this->serializer = $this->container()->get('serializer');
    }
    return $this->serializer;
  }

  /**
   * @return string
   */
  protected function getMethod() {
    return strtolower($this->request->getMethod());
  }

  /**
   * @return string
   */
  protected function getFormat() {
    if (!$format = $this->request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getRequirement('_format')) {
      return $this->getResource()->isAttachment() ? 'stream' : 'json';
    }
    return $format;
  }

  /**
   * @return \Drupal\relaxed\Plugin\rest\resource\RelaxedResourceInterface
   */
  protected function getResource() {
    $plugin_id = $this->request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getDefault('_plugin');
    return $this->container()
      ->get('plugin.manager.rest')
      ->getInstance(array('id' => $plugin_id));
  }

  /**
   * @return array
   */
  protected function getParameters() {
    $parameters = array();
    foreach ($this->request->attributes->get('_route_params') as $key => $parameter) {
      // We don't want private parameters.
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }
    return $parameters;
  }

  /**
   * Helper method for returning error responses.
   *
   * @todo Consider providing a better API where throwing an exception can
   *   provide both error and reason message.
   */
  public function errorResponse(\Exception $e) {
    // Default to 400 Bad Request.
    $status = 400;
    $error = 'bad_request';
    $reason = $e->getMessage();

    if ($e instanceof HttpExceptionInterface) {
      $status = $e->getStatusCode();
    }

    if ($e instanceof UnauthorizedHttpException || $e instanceof AccessDeniedHttpException) {
      $error = 'unauthorized';
    }
    elseif ($e instanceof NotFoundHttpException) {
      $error = 'not_found';
    }
    elseif ($e instanceof ConflictHttpException) {
      $error = 'conflict';
    }
    elseif ($e instanceof PreconditionFailedHttpException) {
      $error = 'file_exists';
    }

    $content = '';
    $headers = array();
    // We shouldn't respond with content for HEAD requests.
    if ($this->request->getMethod() != 'HEAD') {
      $format = $this->getFormat();
      $headers = array('Content-Type' => $this->request->getMimeType($format));
      $data = array('error' => $error, 'reason' => $reason);
      $content = $this->serializer()->serialize($data, $format);
    }
    return new Response($content, $status, $headers);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handle(Request $request) {
    $this->request = $request;

    $method = $this->getMethod();
    $format = $this->getFormat();
    $resource = $this->getResource();

    $content = $this->request->getContent();
    // @todo Check if it's safe to pass all query parameters like this.
    $query = $this->request->query->all();
    $context = array('query' => $query);
    $entity = NULL;
    if (!empty($content)) {
      try {
        $definition = $resource->getPluginDefinition();
        $class = isset($definition['serialization_class'][$method]) ? $definition['serialization_class'][$method] : $definition['serialization_class']['canonical'];
        $entity = $this->serializer()->deserialize($content, $class, $format, $context);
      }
      catch (\Exception $e) {
        return $this->errorResponse($e);
      }
    }

    try {
      $parameters = $this->getParameters();
      /** @var \Drupal\rest\ResourceResponse $response */
      $response = call_user_func_array(array($resource, $method), array_merge($parameters, array($entity, $this->request)));
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }

    // Default to plain text format in case there is no response data.
    // It's only when doing GET on a stream we want to respond with the same.
    // Fall back to JSON in all other cases.
    $response_format = (in_array($request->getMethod(), array('GET', 'HEAD')) && $format == 'stream')
      ? 'stream'
      : 'json';

    $response_data = $response->getResponseData();
    if ($response_data != NULL) {
      try {
        $response_output = $this->serializer()->serialize($response_data, $response_format, $context);
        $response->setContent($response_output);
      }
      catch (\Exception $e) {
        return $this->errorResponse($e);
      }
    }
    if (!$response->headers->get('Content-Type')) {
      $response->headers->set('Content-Type', $this->request->getMimeType($response_format));
    }
    return $response;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function csrfToken() {
    return new Response(drupal_get_token('rest'), 200, array('Content-Type' => 'text/plain'));
  }
}
