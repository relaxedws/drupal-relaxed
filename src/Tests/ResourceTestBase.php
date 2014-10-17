<?php

namespace Drupal\relaxed\Tests;

use Drupal\rest\Tests\RESTTestBase;

abstract class ResourceTestBase extends RESTTestBase {

  public static $modules = array('rest', 'entity_test', 'relaxed');

  /**
   * @var string
   */
  protected $api_root;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  protected $multiversionManager;

  protected function setUp() {
    parent::setUp();
    $this->defaultFormat = 'json';
    $this->defaultMimeType = 'application/json';
    $this->defaultAuth = array('cookie');
    $this->apiRoot = \Drupal::config('relaxed.settings')->get('api_root');

    $this->container
      ->get('entity.definition_update_manager')
      ->applyUpdates();

    $this->multiversionManager = $this->container->get('multiversion.manager');

    $name = $this->randomMachineName();
    $this->workspace = $this->createWorkspace($name);
    $this->workspace->save();

    $this->multiversionManager->setActiveWorkspaceName($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function entityPermissions($entity_type, $operation) {
    $return = parent::entityPermissions($entity_type, $operation);

    // Extending with further entity types.
    if (!$return) {
      switch ($entity_type) {
        case 'entity_test_rev':
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
   * @todo Remove the bulk of function body when https://drupal.org/node/2274153
   *   has been committed. However, the prepending of self::apiRoot needs to be
   *   kept.
   */
  protected function httpRequest($url, $method, $body = NULL, $mime_type = NULL, $headers = NULL) {
    // Keep in overridden method when removing the bulk of this method.
    $url = $this->apiRoot . '/' . $url;

    if ($mime_type === NULL) {
      $mime_type = $this->defaultMimeType;
    }
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))) {
      // GET the CSRF token first for writing requests.
      $token = $this->drupalGet('rest/session/token');
    }
    $curl_options = array();
    switch ($method) {
      case 'GET':
        // Set query if there are additional GET parameters.
        $options = isset($body) ? array('absolute' => TRUE, 'query' => $body) : array('absolute' => TRUE);
        $curl_options = array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_URL => _url($url, $options),
          CURLOPT_NOBODY => FALSE,
        );
        $curl_options[CURLOPT_HTTPHEADER] = $mime_type ? array('Accept: ' . $mime_type) : array();
        break;

      case 'HEAD':
        $options = isset($body) ? array('absolute' => TRUE, 'query' => $body) : array('absolute' => TRUE);
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'HEAD',
          CURLOPT_URL => _url($url, $options),
          CURLOPT_NOBODY => TRUE,
        );
        $curl_options[CURLOPT_HTTPHEADER] = $mime_type ? array('Accept: ' . $mime_type) : array();
        break;

      case 'POST':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => _url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ),
        );
        break;

      case 'PUT':
        $if_match_header = isset($headers['if-match']) ? $headers['if-match'] : '';
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => _url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
            'If-Match: ' . $if_match_header,
          ),
        );
        break;

      case 'PATCH':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => _url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: ' . $mime_type,
            'X-CSRF-Token: ' . $token,
          ),
        );
        break;

      case 'DELETE':
        $options = isset($body) ? array('absolute' => TRUE, 'query' => $body) : array('absolute' => TRUE);
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => _url($url, $options),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('X-CSRF-Token: ' . $token),
        );
        break;
    }

    $response = $this->curlExec($curl_options);
    $headers = $this->drupalGetHeaders();
    $headers = implode("\n", $headers);

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) .
      '<hr />Response headers: ' . $headers .
      '<hr />Response body: ' . $response);

    return $response;
  }

  /**
   * Creates a custom workspace entity.
   */
  protected function createWorkspace($name) {
    $entity = entity_create('workspace', array(
      'id' => $name,
    ));
    return $entity;
  }

  protected function assertHeader($header, $value, $message = '', $group = 'Browser') {
    $header = strtolower($header);
    $header_value = $this->drupalGetHeader($header);
    // Strip attributes such as 'charset' from the content-type header for
    // easier comparison.
    if ($header == 'content-type') {
      list($header_value) = explode(';', $header_value);
    }
    return $this->assertTrue($header_value == $value, $message ? $message : 'HTTP response header ' . $header . ' with value ' . $value . ' found.', $group);
  }
}
