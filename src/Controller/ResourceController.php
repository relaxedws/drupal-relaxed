<?php

namespace Drupal\relaxed\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
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
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Drupal\relaxed\Plugin\ApiResourceManagerInterface $resource_manager
   *   The API resource manager.
   * @param \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface $negotiator_manager
   *   The format negotiator manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ApiResourceManagerInterface $resource_manager, FormatNegotiatorManagerInterface $negotiator_manager, RendererInterface $renderer) {
    $this->resourceManager = $resource_manager;
    $this->negotiatorManager = $negotiator_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.api_resource'),
      $container->get('plugin.manager.format_negotiator'),
      $container->get('renderer')
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
    $format = $this->getFormat($route, $request);

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
      // Select the format negotiator for the request data.
      $negotiator = $this->negotiatorManager->select($format, $method, 'request');
      $serializer = $negotiator->serializer();

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
      $response = $this->renderer->executeInRenderContext($render_context, function() use ($api_resource, $method, $parameters, $entity, $request) {
        return call_user_func_array([$api_resource, $method], array_merge($parameters, [$entity, $request]));
      });

      if (!$render_context->isEmpty()) {
        $render_contexts[] = $render_context->pop();
      }
    }
    catch (\Exception $e) {
      return $this->errorResponse($e);
    }

    // Select the format negotiator for the response data.
    $negotiator = $this->negotiatorManager->select($format, $method, 'response');
    $serializer = $negotiator->serializer();

    $responses = ($response instanceof MultipartResponse) ? $response->getParts() : [$response];

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
  protected function getFormat(Route $route, Request $request) {
    $acceptable_request_formats = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : [];
    $acceptable_content_type_formats = $route->hasRequirement('_content_type_format') ? explode('|', $route->getRequirement('_content_type_format')) : [];
    $acceptable_formats = $request->isMethodSafe() ? $acceptable_request_formats : $acceptable_content_type_formats;

    $requested_format = $request->getRequestFormat();
    $content_type_format = $request->getContentType();

    // If an acceptable format is requested, then use that. Otherwise, including
    // and particularly when the client forgot to specify a format, then use
    // heuristics to select the format that is most likely expected.
    if (in_array($requested_format, $acceptable_formats)) {
      return $requested_format;
    }
    // If a request body is present, then use the format corresponding to the
    // request body's Content-Type for the response, if it's an acceptable
    // format for the request.
    elseif (!empty($request->getContent()) && in_array($content_type_format, $acceptable_content_type_formats)) {
      return $content_type_format;
    }
    // Otherwise, use the first acceptable format.
    elseif (!empty($acceptable_formats)) {
      return $acceptable_formats[0];
    }
    // Default to JSON otherwise.
    else {
      return 'json';
    }
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
