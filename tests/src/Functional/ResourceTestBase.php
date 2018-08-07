<?php

namespace Drupal\Tests\relaxed\Functional;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\workspaces\Entity\Workspace;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;

abstract class ResourceTestBase extends BrowserTestBase {

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
  protected $defaultFormat;

  /**
   * @var string
   */
  protected $defaultMimeType;

  /**
   * @var array
   */
  protected $defaultAuth;

  /**
   * @var string
   */
  protected $apiRoot;

  /**
   * @var \Drupal\workspaces\WorkspaceInterface
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
   * @var \Drupal\workspaces\WorkspaceManagerInterface
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
    $this->workspaceManager = $this->container->get('workspaces.manager');

    $name = $this->randomMachineName();
    $this->workspace = $this->createWorkspace($name);
    $this->workspace->save();
    $this->dbname = $this->workspace->id();

    $this->entityManager = $this->container->get('entity.manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityRepository = $this->container->get('entity.repository');
    $this->revTree = $this->container->get('multiversion.entity_index.rev.tree');
  }

  /**
   * @param $url
   * @param $method
   * @param null $body
   * @param null $mime_type
   * @param null $headers
   * @param null $query
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function httpRequest($url, $method, $body = NULL, $mime_type = NULL, $headers = NULL, $query = NULL) {
    $request_options = [];
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
      $request_options[RequestOptions::HEADERS][] = 'X-CSRF-Token: ' . $token;
    }

    $additional_headers = [];
    if (is_array($headers)) {
      foreach ($headers as $name => $value) {
        $name = mb_convert_case($name, MB_CASE_TITLE);
        $additional_headers[] = "$name: $value";
      }
    }
    // Set query if there are additional parameters.
    switch ($method) {
      case 'GET':
        $get_headers = array_merge(
          [
            'Accept: ' . $mime_type,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $get_headers,
        ];
        break;

      case 'HEAD':
        $head_headers = array_merge(
          [
            'Accept: ' . $mime_type,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $head_headers,
        ];
        break;

      case 'POST':
        $post_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $post_headers,
        ];
        break;

      case 'PUT':
        $put_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $put_headers,
        ];
        break;

      case 'PATCH':
        $patch_headers = array_merge(
          [
            'Content-Type: ' . $mime_type,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $patch_headers,
        ];
        break;

      case 'DELETE':
        $delete_headers = array_merge(
          [
            'X-CSRF-Token: ' . $token,
          ],
          $additional_headers
        );
        $request_options = [
          RequestOptions::HEADERS => $delete_headers,
        ];
        break;
    }

    $request_options[RequestOptions::HTTP_ERRORS] = FALSE;
    $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;
    $request_options = $this->decorateWithXdebugCookie($request_options);
    $client = $this->getHttpClient();
    return $client->request($method, $this->buildUrl($url, ['absolute' => TRUE]), $request_options);
  }

  /**
   * Adds the Xdebug cookie to the request options.
   *
   * @param array $request_options
   *   The request options.
   *
   * @return array
   *   Request options updated with the Xdebug cookie if present.
   */
  protected function decorateWithXdebugCookie(array $request_options) {
    $session = $this->getSession();
    $driver = $session->getDriver();
    if ($driver instanceof BrowserKitDriver) {
      $client = $driver->getClient();
      foreach ($client->getCookieJar()->all() as $cookie) {
        if (isset($request_options[RequestOptions::HEADERS]['Cookie'])) {
          $request_options[RequestOptions::HEADERS]['Cookie'] .= '; ' . $cookie->getName() . '=' . $cookie->getValue();
        }
        else {
          $request_options[RequestOptions::HEADERS]['Cookie'] = $cookie->getName() . '=' . $cookie->getValue();
        }
      }
    }
    return $request_options;
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
    return workspace::create(['id' => $name, 'label' => ucfirst($name)]);
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
