<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\ChangesResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\Changes\Changes;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:changes",
 *   label = "Changes",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\Changes\Changes",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_changes",
 *   },
 *   no_cache = TRUE
 * )
 */
class ChangesResource extends ResourceBase {

  /**
   * @var int
   */
  protected $heartbeat;

  /**
   * @var int
   */
  protected $lastHeartbeat;

  public function get($workspace) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    // @todo: {@link https://www.drupal.org/node/2599930 Use injected container instead.}
    $changes = Changes::createInstance(
      \Drupal::getContainer(),
      $workspace
    );

    /** @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = \Drupal::service('serializer');
    $request = Request::createFromGlobals();

    if ($request->query->get('include_docs') == 'true') {
      $changes->includeDocs(TRUE);
    }
    $last_seq = (int) $request->query->get('since', 0);
    $changes->lastSeq($last_seq);
    $timeout = (int) $request->query->get('timeout', 10000) * 1000;
    $this->heartbeat = (int) $request->query->get('heartbeat', 10000) * 1000;
    $this->lastHeartbeat = $start = $this->now();

    switch ($request->query->get('feed', 'normal')) {
      case 'continuous';
        $response = new StreamedResponse();
        $response->setCallback(function () use ($changes, $serializer, $start, $timeout, $last_seq) {
          do {
            foreach ($changes->getChanges() as $data) {
              echo $serializer->serialize($data, 'json') . "\r\n";
              $this->flush();
              $last_seq = $data['seq'];
            }
            $changes->lastSeq($last_seq);
            echo $this->heartbeat();
            $this->flush();
          } while ($this->now() < ($start + $timeout));
          echo $serializer->serialize(['last_seq' => $last_seq], 'json') . "\r\n";
        });
        break;

      case 'longpoll':
        $response = new StreamedResponse();
        $response->setCallback(function () use ($changes, $serializer, $start, $timeout, $last_seq) {
          do {
            $changed = $changes->hasChanged($last_seq);
            echo $this->heartbeat();
            $this->flush();
          } while (!$changed && $this->now() < ($start + $timeout));
          echo $serializer->serialize($changes, 'json') . "\r\n";
        });
        break;

      default:
        $response = new ResourceResponse($changes, 200);
        break;
    }
    return $response;
  }

  /**
   * Helper method returning the current time in microseconds.
   *
   * @return int
   */
  protected function now() {
    return (int) microtime(TRUE) * 1000000;
  }

  /**
   * Helper method flushing content.
   */
  protected function flush() {
    ob_flush();
    flush();
    $this->lastHeartbeat = $this->now();
  }

  protected function heartbeat() {
    if ($this->heartbeat < ($this->now() - $this->lastHeartbeat)) {
      $this->lastHeartbeat = $this->now();
      return "\r\n";
    }
  }

}
