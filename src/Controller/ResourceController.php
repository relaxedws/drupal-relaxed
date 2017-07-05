<?php

namespace Drupal\relaxed\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Drupal\relaxed\Plugin\ApiResourceManagerInterface;
use Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Route;

class ResourceController implements ContainerAwareInterface, ContainerInjectionInterface {

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
   * The resource configuration storage.
   *
   * @var \Drupal\relaxed\Plugin\ApiResourceManagerInterface
   */
  protected $resourceManager;

  /**
   * @var \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface
   */
  protected $negotiatorManager;

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Drupal\relaxed\Plugin\ApiResourceManagerInterface $resource_manager
   *   The API resource manager.
   */
  public function __construct(ApiResourceManagerInterface $resource_manager, FormatNegotiatorManagerInterface $negotiator_manager) {
    $this->resourceManager = $resource_manager;
    $this->negotiatorManager = $negotiator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.api_resource'),
      $container->get('plugin.manager.format_negotiator')
    );
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface  $route_match
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handle(RouteMatchInterface $route_match, Request $request) {
    $route = $route_match->getRouteObject();
    $method = strtolower($request->getMethod());
    $format = $this->getFormat($route);

    $negotiator = $this->negotiatorManager->select($format);
    $serializer = $negotiator->serializer();

    $api_resource_id = $route->getDefault('_api_resource');
    $api_resource = $this->getResource($api_resource_id);

    $content = $request->getContent();
    $parameters = $this->getParameters($route_match);
    $render_contexts = [];

    // @todo {@link https://www.drupal.org/node/2600500 Check if this is safe.}
    $query = $request->query->all();
    $context = ['query' => $query, 'api_resource_id' => $api_resource_id];

    $entity = NULL;
    $definition = $api_resource->getPluginDefinition();

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
        if ($method == 'put' && !$this->isValidJson($content) && !$api_resource->isAttachment()) {
          $content = $api_resource->putMultipartRequest($request);
        }

        $entity = $serializer->deserialize($content, $class, $format, $context);
      }
      catch (\Exception $e) {
        return $this->errorResponse($e);
      }
    }

    try {
      $render_context = new RenderContext();
      /** @var \Drupal\rest\ResourceResponse $response */
      $response = $this->container->get('renderer')->executeInRenderContext($render_context, function() use ($api_resource, $method, $parameters, $entity, $request) {
        return call_user_func_array([$api_resource, $method], array_merge($parameters, [$entity, $request]));
      });

      if (!$render_context->isEmpty()) {
        $render_contexts[] = $render_context->pop();
      }
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }

    $response_format = (in_array($request->getMethod(), ['GET', 'HEAD']) && $format == 'stream')
      ? 'stream'
      : 'json';

    $responses = ($response instanceof MultipartResponse) ? $response->getParts() : [$response];

    $render_contexts = [];

    foreach ($responses as $response_part) {
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

      if (!$response_part->headers->get('Content-Type')) {
        $response_part->headers->set('Content-Type', $request->getMimeType($response_format));
      }
    }

    if ($request->getMethod() !== 'HEAD') {
      $response->headers->set('Content-Length', strlen($response->getContent()));
    }

    if ($response instanceof CacheableResponseInterface) {
      /** @var \Drupal\relaxed\Plugin\ApiResourceInterface $api_resource */
      $api_resource = $this->getResource($api_resource_id);
      // Add rest config's cache tags.
      $response->addCacheableDependency($api_resource);
    }

    $cacheable_dependencies = [];
    foreach ($render_contexts as $render_context) {
      $cacheable_dependencies[] = $render_context;
    }
    foreach ($parameters as $parameter) {
      if (is_array($parameter)) {
        array_merge($cacheable_dependencies, $parameter);
      }
      else {
        $cacheable_dependencies[] = $parameter;
      }
    }
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_dependencies[] = $cacheable_metadata->setCacheContexts(['url', 'request_format', 'headers:If-None-Match', 'headers:Content-Type', 'headers:Accept']);
    $this->addCacheableDependency($response, $cacheable_dependencies);

    return $response;
  }

  /**
   * @return string
   */
  protected function getFormat(Route $route) {
    if (!$format = $route->getRequirement('_format')) {
      $plugin_id = $route->getDefault('_api_resource');
      return 'json';
      //return $this->getResource($plugin_id)->isAttachment() ? 'stream' : 'json';
    }
    return $format;
  }

  /**
   * @return \Drupal\relaxed\Plugin\rest\resource\RelaxedResourceInterface
   */
  protected function getResource($plugin_id) {
    return $this->resourceManager->createInstance($plugin_id, $this->resourceManager->getDefinition($plugin_id));
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return array
   */
  protected function getParameters(RouteMatchInterface $route_match) {
    $parameters = [];
    foreach ($route_match->getParameters() as $key => $parameter) {
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

    $format = $this->getFormat();
    $headers = ['Content-Type' => $this->request->getMimeType($format)];

    $content = '';
    // We shouldn't respond with content for HEAD requests.
    if ($this->request->getMethod() != 'HEAD') {
      $data = ['error' => $error, 'reason' => $reason];
      $content = $this->serializer()->serialize($data, $format);
    }
    watchdog_exception('Relaxed', $e);
    return new Response($content, $status, $headers);
  }

  /**
   * Generates a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response(\Drupal::csrfToken()->get('relaxed'), 200, ['Content-Type' => 'text/plain']);
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

  /**
   * Adds cacheable dependencies.
   *
   * @param \Drupal\Core\Cache\CacheableResponseInterface
   * @param $parameters
   */
  protected function addCacheableDependency(CacheableResponseInterface $response, $parameters) {
    if (is_array($parameters)) {
      foreach ($parameters as $parameter) {
        $response->addCacheableDependency($parameter);
      }
    }
    else {
      $response->addCacheableDependency($parameters);
    }
  }

}
