<?php

namespace Drupal\relaxed\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
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
   * @todo {@link https://www.drupal.org/node/2599912 Improve to handle error and reason messages more generically.}
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
    watchdog_exception('Relaxed', $e);
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
    $parameters = $this->getParameters();
    $serializer = $this->serializer();
    $render_contexts = [];

    // @todo {@link https://www.drupal.org/node/2600500 Check if this is safe.}
    $query = $this->request->query->all();
    $context = array('query' => $query, 'resource_id' => $resource->getPluginId());
    $entity = NULL;
    $definition = $resource->getPluginDefinition();
    if (!empty($content)) {
      try {
        $class = isset($definition['serialization_class'][$method]) ? $definition['serialization_class'][$method] : $definition['serialization_class']['canonical'];

        // If we have a workspace parameter, pass it to the deserializer.
        foreach ($parameters as $parameter) {
          if ($parameter instanceof WorkspaceInterface) {
            $context['workspace'] = $parameter;
            break;
          }
        }

        // Process a multipart/related PUT request.
        if ($method == 'put' && !$this->isValidJson($content) && !$resource->isAttachment()) {
          $content = $resource->putMultipartRequest($request);
        }

        $entity = $this->serializer()->deserialize($content, $class, $format, $context);
      }
      catch (\Exception $e) {
        return $this->errorResponse($e);
      }
    }

    try {
      $render_context = new RenderContext();
      /** @var \Drupal\rest\ResourceResponse $response */
      $response = $this->container->get('renderer')->executeInRenderContext($render_context, function() use ($resource, $method, $parameters, $entity, $request) {
        return call_user_func_array(array($resource, $method), array_merge($parameters, array($entity, $request)));
      });
      if (!$render_context->isEmpty()) {
        $render_contexts[] = $render_context->pop();
      }
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }

    $response_format = (in_array($request->getMethod(), array('GET', 'HEAD')) && $format == 'stream')
      ? 'stream'
      : 'json';

    $responses = ($response instanceof MultipartResponse) ? $response->getParts() : array($response);

    foreach ($responses as $response_part) {
      try {
        if ($response_data = $response_part->getResponseData()) {
          // Collect bubbleable metadata in a render context.
          $render_context = new RenderContext();
          $response_output = $this->container->get('renderer')->executeInRenderContext($render_context, function() use ($serializer, $response_data, $response_format, $context) {
            return $serializer->serialize($response_data, $response_format, $context);
          });
          if (!$render_context->isEmpty()) {
            $render_contexts[] = $render_context->pop();
          }
          $response_part->setContent($response_output);
        }
      }
      catch (\Exception $e) {
        return $this->errorResponse($e);
      }
      if (!$response_part->headers->get('Content-Type')) {
        $response_part->headers->set('Content-Type', $this->request->getMimeType($response_format));
      }
    }

    foreach ($render_contexts as $render_context) {
      $response->addCacheableDependency($render_context);
    }
    foreach ($parameters as $parameter) {
      if (is_array($parameter)) {
        foreach ($parameter as $item) {
          $response->addCacheableDependency($item);
        }
      }
      else {
        $response->addCacheableDependency($parameter);
      }
    }
    $response->addCacheableDependency($this->container->get('config.factory')->get('rest.settings'));
    $cacheable_metadata = new CacheableMetadata();
    $response->addCacheableDependency($cacheable_metadata->setCacheContexts(['url.query_args', 'request_format', 'headers:If-None-Match']));

    if ($request->getMethod() !== 'HEAD') {
      $response->headers->set('Content-Length', strlen($response->getContent()));
    }

    return $response;
  }

  /**
   * Generates a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response(\Drupal::csrfToken()->get('rest'), 200, array('Content-Type' => 'text/plain'));
  }

  /**
   * Check if a string is a valid json.
   *
   * @param $string
   *
   * @return bool
   */
  protected function isValidJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
  }

}
