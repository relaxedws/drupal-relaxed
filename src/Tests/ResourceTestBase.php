<?php

namespace Drupal\relaxed\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\rest\Tests\RESTTestBase;

abstract class ResourceTestBase extends RESTTestBase {

  public static $modules = array(
    'entity_test',
    'file',
    'multiversion',
    'rest',
    'relaxed',
    'relaxed_test'
  );

  protected $strictConfigSchema = FALSE;

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
    $this->defaultAuth = array('cookie');
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
   */
  protected function entityPermissions($entity_type, $operation) {
    $return = parent::entityPermissions($entity_type, $operation);

    // Extending with further entity types.
    if (!$return) {
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
    }
    return $return;
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
      $query[] = ['_format' => $this->defaultFormat];
    }
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))) {
      // GET the CSRF token first for writing requests.
      $token = $this->drupalGet('rest/session/token');
    }
    $additional_headers = array();
    if (is_array($headers)) {
      foreach ($headers as $name => $value) {
        $name = mb_convert_case($name, MB_CASE_TITLE);
        $additional_headers[] = "$name: $value";
      }
    }
    // Set query if there are additional parameters.
    $options = isset($query) ? array('absolute' => TRUE, 'query' => $query) : array('absolute' => TRUE);
    $curl_options = array();
    switch ($method) {
      case 'GET':
        $get_headers = array_merge(
          array(
            'Accept: ' . $mime_type,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $get_headers,
        );
        break;

      case 'HEAD':
        $head_headers = array_merge(
          array(
            'Accept: ' . $mime_type,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'HEAD',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => TRUE,
          CURLOPT_HTTPHEADER => $head_headers,
        );
        break;

      case 'POST':
        $post_headers = array_merge(
          array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $post_headers,
        );
        break;

      case 'PUT':
        $put_headers = array_merge(
          array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $put_headers,
        );
        break;

      case 'PATCH':
        $patch_headers = array_merge(
          array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => $this->buildUrl($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $patch_headers,
        );
        break;

      case 'DELETE':
        $delete_headers = array_merge(
          array(
            'X-CSRF-Token: ' . $token,
          ),
          $additional_headers
        );
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => $this->buildUrl($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => $delete_headers,
        );
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
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($name) {
    return workspace::create(['machine_name' => $name, 'label' => ucfirst($name), 'type' => 'basic']);
  }

}
