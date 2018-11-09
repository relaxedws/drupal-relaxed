<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\rest\Plugin\ResourceBase as CoreResourceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class ResourceBase extends CoreResourceBase implements RelaxedResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $this->serializerFormats = array_merge($this->serializerFormats, ['mixed', 'related']);
    $collection = new RouteCollection();
    $definition = $this->getPluginDefinition();
    $api_root = trim(\Drupal::config('relaxed.settings')->get('api_root'), '/');
    $route_name = strtr($this->pluginId, ':', '.');

    foreach ($this->availableMethods() as $method) {
      $no_cache = isset($definition['no_cache']) ? $definition['no_cache'] : FALSE;
      $canonical = $definition['uri_paths']['canonical'];
      $route = $this->getBaseRoute($api_root . $canonical, $method, $no_cache);

      $lower_method = strtolower($method);
      if (isset($definition['uri_paths'][$lower_method])) {
        $route->setPath($definition['uri_paths'][$lower_method]);
      }

      // @todo {@link https://www.drupal.org/node/2600450 Move this parameter
      // logic to a generic route enhancer instead.}
      $parameters = [];
      foreach (['db', 'docid'] as $parameter) {
        if (strpos($route->getPath(), '{' . $parameter . '}')) {
          $parameters[$parameter] = ['type' => 'relaxed:' . $parameter];
        }
      }
      if (!empty($definition['uri_parameters']['canonical'])) {
        foreach ($definition['uri_parameters']['canonical'] as $parameter => $type) {
          $parameters[$parameter] = ['type' => $type];
        }
      }
      if ($parameters) {
        $route->addOptions(['parameters' => $parameters]);
      }

      if ($method === 'PUT' && !$this->isAttachment()) {
        $route->addRequirements(['_content_type_format' => implode('|', $this->serializerFormats)]);
      }

      // Note that '_format' and '_content_type_format' route requirements are
      // added in ResourceRoutes::getRoutesForResourceConfig().
      $collection->add("$route_name.$method", $route);

    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method, $no_cache = NULL) {
    return new Route($canonical_path, [
      '_controller' => 'Drupal\relaxed\Controller\ResourceController::handle',
      '_plugin' => $this->pluginId,
    ],
      $this->getBaseRouteRequirements($method),
      [
        'no_cache' => $no_cache ? $no_cache : FALSE,
      ],
      '',
      [],
      // The HTTP method is a requirement for this route.
      [$method]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRouteRequirements($method) {
    $lower_method = strtolower($method);
    // Every route MUST have requirements that result in the access manager
    // having access checks to check. If it does not, the route is made
    // inaccessible. So, we default to granting access to everyone. If a
    // permission exists, then we add that below. The access manager requires
    // that ALL access checks must grant access, so this still results in
    // correct behavior.
    $requirements = [
      '_access' => 'TRUE',
    ];

    // Allow pull or push permissions depending on the method.
    $push_and_pull_permissions = 'perform push replication';
    if ($method === 'GET') {
      $push_and_pull_permissions .= '+perform pull replication';
    }

    // Only specify route requirements if the default permission exists. For any
    // more advanced route definition, resource plugins extending this base
    // class must override this method.
    $permission = "restful $lower_method $this->pluginId";
    if (isset($this->permissions()[$permission])) {
      $requirements['_permission'] = $permission . "+$push_and_pull_permissions";
    }

    return $requirements;
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
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException(implode('. ', $messages));
    }
  }

  /**
   * @param mixed $workspace
   */
  protected function checkWorkspaceExists($workspace) {
    if (!$workspace instanceof WorkspaceInterface || !$workspace->isPublished()) {
      throw new NotFoundHttpException(t('Workspace does not exist.'));
    }
  }

}
