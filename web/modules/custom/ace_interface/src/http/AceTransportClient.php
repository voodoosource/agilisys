<?php

namespace Drupal\ace_interface\http;

use Drupal\ace_interface\DynamicsConnectionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Psr7\Request;
use Drupal\ace_interface\http\AceGuzzleClient;

//use Drupal\ace_interface\http\AceCurlClient;
use Drupal\ace_interface\http\AceOAuthClient;


class AceTransportClient {

  /**
   * @var \Drupal\ace_interface\http\AceGuzzleClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $headers;

  protected $auth;

  protected $authUsername;

  protected $authPassword;

  protected $authType;

  protected $clientType;

  protected $config;

  /**
   * @var \Drupal\ace_interface\http\AceOAuthClient
   */
  protected $authClient;

  /**
   * @var \Drupal\ace_interface\http\AceCurlClient
   */
  protected $curlClient;

  protected $connection;

  protected $tokenManager;


  /**
   * AceTransportClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ace_interface\http\AceOAuthClient $authClient
   * @param \Drupal\ace_interface\http\AceGuzzleClient $client
   * @param \Drupal\ace_interface\http\AceCurlClient $curlClient
   * @param TokenManager $tokenManager
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AceOAuthClient $authClient,
    AceGuzzleClient $client,
    AceCurlClient $curlClient,
    TokenManager $tokenManager
  ) {
    $this->configFactory = $config_factory;

    $this->client = $client;
    $this->authClient = $authClient;
    $this->curlClient = $curlClient;
    $this->tokenManager = $tokenManager;

    $this->setConfig($this->configFactory->get($this->getConfigContainer()));
  }

  public function setConnection(DynamicsConnectionInterface $connection) {
    $this->connection = $connection;
    $this->authClient->setConnection($connection);
    // This call to setConfig does nothing, its only needed to reset the member variables.
    // And set the authClient Connection.
    $this->setConfig($this->configFactory->get($this->getConfigContainer()));
  }

  public function getConfig($key){
    if ($this->connection instanceof DynamicsConnectionInterface) {
      return $this->connection->get($key);
    }

    return $this->config->get($key);
  }

  public function setConfig($config) {
    $this->config = $config;

    $this->authClient->setConfig($config);

    if ($this->connection instanceof DynamicsConnectionInterface) {
      $this->authClient->setConnection($this->connection);
    }
    $this->clientType = $this->getConfig('client_type');
    $this->client->base($this->getConfig('crm_host'));

    if ($this->getConfig('crm_host')) {
      $this->client->base("https://{$this->getConfig('crm_host')}");
    }

    switch ($this->clientType) {
      case 'guzzle':
        $this->headers = [
          'Accept' => ['application/json'],
          'Content-Type' => ['application/json'],
          "If-None_Match" => ['null'],
          "OData-MaxVersion" => ['4.0'],
          "OData-Version" => ["4.0"],
          "cache-control" => ["no-cache"],
          "Connection" => ["keep-alive"],
        ];
        break;

      default:
        $this->headers = NULL;
    }

    $this->authUsername = $this->getConfig('authentication_username');
    $this->authPassword = $this->getConfig('authentication_password');
    $this->authType = $this->getConfig('authentication_type');
    $this->auth = [$this->authUsername, $this->authPassword, $this->authType];
  }

  public function getConfigContainer() {
    $submission_method = $this->configFactory->get('ace_interface.settings')
      ->get('submission_method');

      switch ($submission_method) {
        case 'submission_api':
          $config = 'ace_submission_framework.settings';
          break;

        default:
          $config = 'ace_dynamics.settings';
      }

    return $config;
  }

  public function get($uri) {
    $response = NULL;
    $headers = $this->headers;

    switch ($this->clientType) {
      case 'guzzle':

        $options['headers'] = $headers;

        switch ($this->authType) {
          case 'oauth':
            $url = "https://" . $this->getConfig('crm_host') . $uri;
            $options['curl'] = $this->getSecureConnectionCurlOptions($url);
            $options['headers']['Authorization'] = $this->tokenManager->getResourceToken($this->getConfig('scope'));

            $response = $this->client->get($uri, $options, $this->getConfig('debug_connection'));
            break;

          case 'ntlm':
            $options['curl'] = [
              CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
              CURLOPT_USERPWD => $this->authUsername . ":" . $this->authPassword,
              CURLOPT_SSL_VERIFYPEER => FALSE,
              CURLOPT_SSL_VERIFYHOST => FALSE,
            ];

            $response = $this->client->get($uri, $options, $this->getConfig('debug_connection'));
            break;

        } //authType switch

        break; //case guzzle

      case 'curl':
        $response = $this->curlClient->get($uri, $headers);
        break;
    }

    return $response;
  }

  public function post($uri, $body) {
    $response = NULL;
    $headers = $this->headers;

    switch ($this->clientType) {
      case 'guzzle':

        $options['headers'] = $headers;

        switch ($this->authType) {

          case 'oauth':
            $url = "https://" . $this->getConfig('crm_host') . $uri;
            $options['curl'] = $this->getSecureConnectionCurlOptions($url);
            $options['headers']['Authorization'] = $this->tokenManager->getResourceToken($this->getConfig('scope'));

            $response = $this->client->post($uri, $options, $body, $this->getConfig('debug_connection'));
            break;

          case 'ntlm':

            $options['body'] = $body;

            $options['curl'] = [
              CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
              CURLOPT_USERPWD => $this->authUsername . ":" . $this->authPassword,
              CURLOPT_SSL_VERIFYPEER => FALSE,
              CURLOPT_SSL_VERIFYHOST => FALSE,
            ];

            $response = $this->client->post($uri, $options, $body, $this->getConfig('debug_connection'));
            break;

        } //authType switch

        break; //case guzzle

      case 'curl':
        $response = $this->curlClient->post($uri, $headers, $body);
        break;

    }

    return $response;
  }

  public function patch($uri, $body) {

    $response = NULL;
    $headers = $this->headers;

    switch ($this->clientType) {
      case 'guzzle':
        $options['headers'] = $headers;

        switch ($this->authType) {

          case 'oauth':
            $url = "https://" . $this->getConfig('crm_host') . $uri;
            $options['curl'] = $this->getSecureConnectionCurlOptions($url);
            $options['headers']['Authorization'] = $this->tokenManager->getResourceToken($this->getConfig('scope'));

            $response = $this->client->patch($uri, $options, $body, $this->getConfig('debug_connection'));
            break;

          case 'ntlm':
            $options['curl'] = [
              CURLOPT_HTTPAUTH => CURLAUTH_NTLM,
              CURLOPT_USERPWD => $this->authUsername . ":" . $this->authPassword,
              CURLOPT_SSL_VERIFYPEER => FALSE,
              CURLOPT_SSL_VERIFYHOST => FALSE,
            ];

            $response = $this->client->patch($uri, $options, $body, $this->getConfig('debug_connection'));
            break;
        }

        break;

      case 'curl':
        $response = $this->curlClient->patch($uri, $this->curlHeaders, $body);
        break;
    }

    return $response;
  }

  public function getLastRequest() {
    return $this->client->getLastRequest();
  }

  public function getLastBody() {
    return $this->client->getLastBody();
  }

  public function getLastResponse() {
    return $this->client->getLastResponse();
  }

  /**
   * Adds curl options if secure connection used.
   *
   * @param $url
   *
   * @return array|mixed
   */
  private function getSecureConnectionCurlOptions($url) {
    $secure_connection = $this->configFactory->get('ace_interface.settings')
      ->get('secure_connection');
    $soip_constant_name = $this->configFactory->get('ace_interface.settings')
      ->get('soip_constant_name');
    $disable_ssl_verify = $this->configFactory->get('ace_interface.settings')
      ->get('disable_ssl_verify');

    if ($secure_connection) {
      $host = parse_url($url, PHP_URL_HOST);
      $localhost = "127.0.0.1";
      $resolve_host = [
        sprintf("%s:%d:%s", $host, constant($soip_constant_name), $localhost),
      ];
      $options = [
        CURLOPT_RESOLVE => $resolve_host,
        CURLOPT_PORT => constant($soip_constant_name),
      ];
      if ($disable_ssl_verify) {
        $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
        $options[CURLOPT_SSL_VERIFYHOST] = FALSE;
      }
      else {
        $options[CURLOPT_SSL_VERIFYPEER] = TRUE;
      }

      return $options;
    }
  }

}
