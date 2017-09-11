<?php

namespace Drupal\relaxed\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\simpletest\WebTestBase;

abstract class ResourceTestBase extends WebTestBase {

  public static $modules = [
    'entity_test',
    'file',
    'multiversion',
    'relaxed',
    'relaxed_test',
  ];

  /**
   * @var string
   */
  protected $apiRoot;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * @var string
   */
  protected $dbname;

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  protected $multiversionManager;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * @var \Drupal\multiversion\Entity\Index\RevisionTreeIndexInterface
   */
  protected $revTree;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->defaultAuth = ['cookie'];
    $this->apiRoot = \Drupal::config('relaxed.settings')->get('api_root');

    $this->multiversionManager = $this->container->get('multiversion.manager');
    $this->workspaceManager = $this->container->get('workspace.manager');

    $name = $this->randomMachineName();
    $this->workspace = $this->createWorkspace($name);
    $this->workspace->save();
    $this->dbname = $this->workspace->getMachineName();

    $this->entityManager = $this->container->get('entity.manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityRepository = $this->container->get('entity.repository');
    $this->revTree = $this->container->get('multiversion.entity_index.rev.tree');
  }

  /**
   * {@inheritdoc}
   *
   * @todo {@link https://www.drupal.org/node/2600494 Simplify this method}
   *   when {@link https://drupal.org/node/2274153 core tests supporting HEAD
   *   requests} gets committed.
   */
  protected function httpRequest($url, $method, $body = NULL, $mime_type = NULL, $headers = NULL, $query = NULL) {
    // Keep in overridden method when removing the bulk of this method.
    $url = $this->apiRoot . '/' . $url;

    if ($mime_type === NULL) {
      $mime_type = $this->defaultMimeType;
    }

    if ($mime_type === $this->defaultMimeType && !isset($query['_format'])) {
      $query['_format'] = $this->defaultFormat;
    }

    if (!in_array($method, ['GET', 'HEAD'])) {
      // GET the CSRF token first for writing requests.
      $token = $this->drupalGet('session/token');
    }

    $additional_headers = [];
    if (is_array($headers)) {
      foreach ($headers as $name => $value) {
        $name = mb_convert_case($name, MB_CASE_TITLE);
        $additional_headers[] = "$name: $value";
      }
    }
    // Set query if there are additional parameters.
    $options = isset($query) ? ['absolute' => TRUE, 'query' => $query] : ['absolute' => TRUE];
    $curl_options = [];
    switch ($method) {
      case 'GET':
        $get_headers = array_merge(
          [
            'Accept: ' . $mime_type,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $get_headers,
        ];
        break;

      case 'HEAD':
        $head_headers = array_merge(
          [
            'Accept: ' . $mime_type,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'HEAD',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => TRUE,
          CURLOPT_HTTPHEADER => $head_headers,
        ];
        break;

      case 'POST':
        $post_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $post_headers,
        ];
        break;

      case 'PUT':
        $put_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $put_headers,
        ];
        break;

      case 'PATCH':
        $patch_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, ['absolute' => TRUE]),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $patch_headers,
        ];
        break;

      case 'DELETE':
        $delete_headers = array_merge(
          [
            'X-CSRF-Token: ' . $token,
          ],
          $additional_headers
        );
        $curl_options = [
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $delete_headers,
        ];
        break;
    }

    $response = $this->curlExec($curl_options);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    $headers = $this->drupalGetHeaders();

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) .
      '<hr />Response headers: ' . nl2br(print_r($headers, TRUE)) .
      '<hr />Response body: ' . $response);

    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * This method is overridden to deal with a cURL quirk: the usage of
   * CURLOPT_CUSTOMREQUEST cannot be unset on the cURL handle, so we need to
   * override it every time it is omitted.
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    unset($this->response);

    if (!isset($curl_options[CURLOPT_CUSTOMREQUEST])) {
      if (!empty($curl_options[CURLOPT_HTTPGET])) {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
      }
      if (!empty($curl_options[CURLOPT_POST])) {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'POST';
      }
    }
    return parent::curlExec($curl_options, $redirect);
  }

  /**
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($name) {
    return workspace::create(['machine_name' => $name, 'label' => ucfirst($name), 'type' => 'basic']);
  }

  /**
   * {@inheritdoc}
   */
  protected function entityPermissions($entity_type, $operation) {
    if (in_array($entity_type, array('entity_test_rev', 'entity_test_local'))) {
      switch ($operation) {
        case 'view':
          return array('view test entity');
        case 'create':
        case 'update':
        case 'delete':
          return array('administer entity_test content');
      }
    }

    return [];
  }

}
