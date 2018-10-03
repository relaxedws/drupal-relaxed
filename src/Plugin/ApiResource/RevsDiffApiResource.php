<?php

namespace Drupal\relaxed\Plugin\ApiResource;

use Drupal\relaxed\Http\ApiResourceResponse;
use Drupal\workspaces\WorkspaceInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @ApiResource(
 *   id = "revs_diff",
 *   label = "Revisions diff",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\RevisionDiff\RevisionDiff",
 *   },
 *   path = "/{db}/_revs_diff",
 *   no_cache = TRUE
 * )
 */
class RevsDiffApiResource extends ApiResourceBase {

  /**
   * @param $workspace
   * @param $revs_diff
   *
   * @return \Drupal\relaxed\Http\ApiResourceResponse
   */
  public function post($workspace, $revs_diff) {
    if (!$workspace instanceof WorkspaceInterface) {
      throw new BadRequestHttpException(t('Workspace does not exist'));
    }
    if (empty($revs_diff)) {
      throw new BadRequestHttpException(t('No content info received'));
    }

    return new ApiResourceResponse($revs_diff, 200);
  }

}
