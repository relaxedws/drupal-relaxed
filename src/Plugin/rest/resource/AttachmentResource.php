<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AttachmentResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "relaxed:attachment",
 *   label = "Attachment",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}/{attname}",
 *   }
 * )
 */
class AttachmentResource extends ResourceBase {

}