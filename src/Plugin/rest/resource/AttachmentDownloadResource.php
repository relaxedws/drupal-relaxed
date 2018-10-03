<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\FileInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\file\Entity\File;

/**
 * @RestResource(
 *   id = "relaxed:attachmentdownload",
 *   label = "Attachment Download",
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}/{filename}",
 *   },
 *   no_cache = TRUE
 * )
 *
 * @todo {@link https://www.drupal.org/node/2600428 Implement real ETag.}
 */
class AttachmentDownloadResource extends ResourceBase {



  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\FileInterface $file
   * @param string $filename
   * @return BinaryFileResponse
   */
  public function get($workspace, $file, $filename) {
    $this->checkWorkspaceExists($workspace);
    //print_r($entity);
    //$file = File::load($entity);
    if (!is_object($file)) {
      throw new NotFoundHttpException();
    }
    $uri = $file->getFileUri();
    $filename = $file->getFilename();

    // File doesn't exist
    // This may occur if the download path is used outside of a formatter and the file path is wrong or file is gone.
    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    //$headers = $this->moduleHandler()->invokeAll('file_download', [$uri]);

   /* foreach ($headers as $result) {
      if ($result == -1) {
        throw new AccessDeniedHttpException();
      }
    }*/

    $mimetype = Unicode::mimeHeaderEncode($file->getMimeType());
    $headers = [
      'Content-Type'              => $mimetype,
      'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
      'Content-Length'            => $file->getSize(),
      'Content-Transfer-Encoding' => 'binary',
      'Pragma'                    => 'no-cache',
      'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
      'Expires'                   => '0',
      'Accept-Ranges'             => 'bytes',
    ];



    // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    // sets response as not cacheable if the Cache-Control header is not
    // already modified. We pass in FALSE for non-private schemes for the
    // $public parameter to make sure we don't change the headers.
    
    return new BinaryFileResponse($uri, 200, $headers, TRUE);

  }

}
