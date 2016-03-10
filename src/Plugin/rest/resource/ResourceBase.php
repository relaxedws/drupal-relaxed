<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ResourceBase.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\Plugin\ResourceBase as CoreResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class ResourceBase extends CoreResourceBase implements RelaxedResourceInterface {

  /**
   * @param $workspace
   *
   * @return ResourceResponse
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function options($workspace) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new NotFoundHttpException();
    }
    $response = new ResourceResponse(NULL, 204);
    $response->addCacheableDependency($workspace);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function availableMethods() {
    $methods = parent::availableMethods();

    // Indiscriminately patch in OPTIONS as an accepted method, to facilitate
    // CORS for all RELAXed endpoints.
    if (!in_array('OPTIONS', $methods)) {
      $methods[] = 'OPTIONS';
    }

    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $this->serializerFormats = array_merge($this->serializerFormats, array('mixed', 'related'));
    $collection = new RouteCollection();
    $definition = $this->getPluginDefinition();
    $api_root = trim(\Drupal::config('relaxed.settings')->get('api_root'), '/');
    $route_name = strtr($this->pluginId, ':', '.');

    foreach ($this->availableMethods() as $method) {
      // HEAD and GET are equivalent as per RFC and handled by the same route.
      // @see \Symfony\Component\Routing\Matcher::matchCollection()
      if ($method == 'HEAD') {
        continue;
      }

      // Allow pull or push permissions depending on the method.
      $permissions = 'perform push replication';
      if (in_array($method, ['GET', 'OPTIONS'])) {
        $permissions .= '+perform pull replication';
      }

      $method_lower = strtolower($method);
      $route = new Route($api_root . $definition['uri_paths']['canonical'], array(
        '_controller' => 'Drupal\relaxed\Controller\ResourceController::handle',
        '_plugin' => $this->pluginId,
      ), array(
        '_permission' => "restful " . $method_lower . " $this->pluginId" . "+$permissions",
      ),
        array(
          'no_cache' => isset($definition['no_cache']) ? $definition['no_cache'] : FALSE,
        ),
        '',
        array(),
        // The HTTP method is a requirement for this route.
        array($method)
      );

      if (isset($definition['uri_paths'][$method_lower])) {
        $route->setPath($definition['uri_paths'][$method_lower]);
      }

      // @todo {@link https://www.drupal.org/node/2600450 Move this parameter
      // logic to a generic route enhancer instead.}
      $parameters = array();
      foreach (array('db', 'docid') as $parameter) {
        if (strpos($route->getPath(), '{' . $parameter . '}')) {
          $parameters[$parameter] = array('type' => 'relaxed:' . $parameter);
        }
      }
      if (!empty($definition['uri_parameters']['canonical'])) {
        foreach ($definition['uri_parameters']['canonical'] as $parameter => $type) {
          $parameters[$parameter] = array('type' => $type);
        }
      }
      if ($parameters) {
        $route->addOptions(array('parameters' => $parameters));
      }

      switch ($method) {
        case 'POST':
        case 'PUT':
          // Restrict on the Content-Type header.
          if (!$this->isAttachment()) {
            $route->addRequirements(array('_content_type_format' => implode('|', $this->serializerFormats)));
          }
          $collection->add("$route_name.$method", $route);
          break;

        case 'GET':
        case 'OPTIONS':
          $collection->add("$route_name.$method", $route);
          break;

        case 'DELETE':
          foreach ($this->serializerFormats as $format) {
            $format_route = clone $route;
            $format_route->addRequirements(array('_format' => $format));
            $collection->add("$route_name.$method.$format", $format_route);
          }
          break;
      }
    }
    return $collection;
  }

  public function isAttachment() {
    return (substr($this->getPluginId(), -strlen('attachment')) == 'attachment');
  }

  protected function validate(ContentEntityInterface $entity) {
    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if (count($violations) > 0) {
      $messages = array();
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException(implode('. ', $messages));
    }
  }
}
