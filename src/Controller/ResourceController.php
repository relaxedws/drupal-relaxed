<?php

namespace Drupal\relaxed\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\relaxed\HttpMultipart\HttpFoundation\MultipartResponse;
use Drupal\relaxed\Plugin\ApiResourceInterface;
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
use Symfony\Component\Serializer\Serializer;

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
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $token;

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Drupal\relaxed\Plugin\ApiResourceManagerInterface $resource_manager
   *   The API resource manager.
   * @param \Drupal\relaxed\Plugin\FormatNegotiatorManagerInterface $negotiator_manager
   *   The format negotiator manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token
   *   The token manager.
   */
  public function __construct(ApiResourceManagerInterface $resource_manager, FormatNegotiatorManagerInterface $negotiator_manager, RendererInterface $renderer, CsrfTokenGenerator $token) {
    $this->resourceManager = $resource_manager;
    $this->negotiatorManager = $negotiator_manager;
    $this->renderer = $renderer;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.api_resource'),
      $container->get('plugin.manager.format_negotiator'),
      $container->get('renderer'),
      $container->get('csrf_token')
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

    $api_resource_id = $route->getDefault('_api_resource');
    $api_resource = $this->getResource($api_resource_id);

    $content_type_format = $this->getContentTypeFormat($route, $request, $api_resource);

    // Select the format negotiator for the request data.
    $negotiator = $this->negotiatorManager->select($content_type_format, $method, 'request');
    $serializer = $negotiator->serializer();

    $content = $request->getContent();
    $parameters = $this->getParameters($route_match);
    $render_contexts = [];

    // @todo {@link https://www.drupal.org/node/2600500 Check if this is safe.}
    $query = $request->query->all();
    $context = [
      'query' => $query,
      'api_resource_id' => $api_resource_id,
    ];

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
        if (($method == 'put') && !$this->isValidJson($content) && ($content_type_format === 'json')) {
          $content = $api_resource->putMultipartRequest($request);
        }

        $entity = $serializer->deserialize($content, $class, $content_type_format, $context);
      }
      catch (\Exception $e) {
        return $this->errorResponse($e, $content_type_format, $serializer, $request);
      }
    }

    try {
      $render_context = new RenderContext();
      /** @var \Drupal\relaxed\Http\ApiResourceResponse $response */
      $response = $this->renderer->executeInRenderContext($render_context, function() use ($api_resource, $method, $parameters, $entity, $request) {
        return call_user_func_array([$api_resource, $method], array_merge($parameters, [$entity, $request]));
      });

      if (!$render_context->isEmpty()) {
        $render_contexts[] = $render_context->pop();
      }
    }
    catch (\Exception $e) {
      return $this->errorResponse($e, $content_type_format, $serializer, $request);
    }

    // Select the format negotiator for the response data.
    $response_format = $this->getResponseFormat($route, $request, $api_resource);

    $negotiator = $this->negotiatorManager->select($response_format, $method, 'response');
    $serializer = $negotiator->serializer();

    // @todo This is not nice, it's hacky. Find a nicer way to get response
    // formats based on the chosen negotiator. We might have to switch to one
    // format per negotiator.
    // If the format for the response is not in the chosen negotiator, use the
    // first one from the definition.
    $negotiator_formats = $negotiator->getPluginDefinition()['formats'];
    if (!in_array($response_format, $negotiator_formats, TRUE)) {
      // Use the first from the chosen negotiator.
      $response_format = reset($negotiator_formats);
    }

    $responses = ($response instanceof MultipartResponse) ? $response->getParts() : [$response];

    $render_contexts = [];

    foreach ($responses as $response_part) {
      if ($response_data = $response_part->getResponseData()) {
        // Collect bubbleable metadata in a render context.
        $render_context = new RenderContext();
        $response_output = $this->renderer->executeInRenderContext($render_context, function() use ($serializer, $response_data, $response_format, $context) {
          return $serializer->serialize($response_data, $response_format, $context);
        });

        if (!$render_context->isEmpty()) {
          $render_contexts[] = $render_context->pop();
        }

        $response_part->setContent($response_output);
      }

      if (!$response_part->headers->has('Content-Type')) {
        $response_part->headers->set('Content-Type', $request->getMimeType($response_format));
      }
    }

    if ($method !== 'head') {
      $response->headers->set('Content-Length', strlen($response->getContent()));
    }

    if ($response instanceof CacheableResponseInterface) {
      /** @var \Drupal\relaxed\Plugin\ApiResourceInterface $api_resource */
      $api_resource = $this->getResource($api_resource_id);
      // Add API resource and format negotiator as dependencies.
      $response->addCacheableDependency($api_resource);
      $response->addCacheableDependency($negotiator);
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
   * Gets the response format.
   *
   * This uses the content type format if an actual format is no specified.
   *
   * @return string
   */
  protected function getResponseFormat(Route $route, Request $request, ApiResourceInterface $api_resource) {
    $api_resource_formats = $api_resource->getPluginDefinition()['allowed_formats'];
    $acceptable_request_formats = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : [];
    //$acceptable_content_type_formats = $route->hasRequirement('_content_type_format') ? explode('|', $route->getRequirement('_content_type_format')) : [];
    //$acceptable_formats = $request->isMethodSafe() ? $acceptable_request_formats : $acceptable_content_type_formats;
    $acceptable_formats = !empty($api_resource_formats) ? $api_resource_formats : $acceptable_request_formats;

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
    elseif (!empty($request->getContent()) && in_array($content_type_format, $acceptable_formats)) {
      return $content_type_format;
    }
    // Otherwise, use the first acceptable format.
    elseif (!empty($acceptable_formats)) {
      return $acceptable_formats[0];
    }
    // Do we want this to be JSON instead.
    else {
      return 'json';
    }
  }

  /**
   * Gets the content type format.
   *
   * @return string
   */
  protected function getContentTypeFormat(Route $route, Request $request, ApiResourceInterface $api_resource) {
    $api_resource_formats = $api_resource->getPluginDefinition()['allowed_formats'];
    $acceptable_content_type_formats = $route->hasRequirement('_content_type_format') ? explode('|', $route->getRequirement('_content_type_format')) : [];
    $acceptable_formats = !empty($api_resource_formats) ? $api_resource_formats : $acceptable_content_type_formats;

    $content_type_format = $request->getContentType();

    // If a request body is present, then use the format corresponding to the
    // request body's Content-Type for the response, if it's an acceptable
    // format for the request.
    if (in_array($content_type_format, $acceptable_formats)) {
      return $content_type_format;
    }
    // Otherwise, use the first acceptable format.
    elseif (!empty($acceptable_formats)) {
      return reset($acceptable_formats);
    }
    // Default and assume JSON otherwise.
    else {
      return 'json';
    }
  }

  /**
   * @return \Drupal\relaxed\Plugin\ApiResourceInterface
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
  protected function errorResponse(\Exception $e, $format, Serializer $serializer, Request $request) {
    // Default to 400 Bad Request.
    $status = 400;
    $error = 'bad_request';
    $reason = $e->getMessage();
    $headers = [];

    if ($e instanceof HttpExceptionInterface) {
      $status = $e->getStatusCode();
      // Use any headers passed from the HTTP exception.
      $headers = $e->getHeaders();
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

    // Always try and set the content type for the response.
    $headers['Content-Type'] = $request->getMimeType($format);

    $content = '';

    // We shouldn't respond with content for HEAD requests.
    if ($request->getMethod() != 'HEAD') {
      $data = ['error' => $error, 'reason' => $reason];
      $content = $serializer->serialize($data, $format);
    }

    watchdog_exception('relaxed', $e);

    return new Response($content, $status, $headers);
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
