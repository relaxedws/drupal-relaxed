<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rest\Plugin\ResourceBase as CoreResourceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class ResourceBase extends CoreResourceBase implements RelaxedResourceInterface {

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
}
