<?php
/**
 * Created by PhpStorm.
 * User: agilisys
 * Date: 25/03/2019
 * Time: 19:48
 */

namespace Drupal\ace_interface\http;

use Drupal\ace_interface_submission\DynamicsConnectionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class AceOAuthClient {

  /**
   * @var \Drupal\ace_interface\http\AceGuzzleClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $config;

  protected $connection;

  protected $defaultCache;

  /**
   * AceOAuthClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ace_interface\http\AceGuzzleClient $client
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $defaultCache) {
    $this->configFactory = $config_factory;
    $this->setConfig($this->configFactory->get($this->getConfigKey()));

    $this->defaultCache = $defaultCache;

    $this->setClient($config_factory);
  }

  public function setConnection(DynamicsConnectionInterface $connection) {
    $this->connection = $connection;
  }
  /**
   * @param \Drupal\Core\Config\ImmutableConfig $config
   */
  public function setConfig($config) {
    $this->config = $config;
    $this->setClient($this->configFactory);
  }

  /**
   * @param $key
   * @return string
   */
  public function getConfig($key){
    if ($this->connection instanceof DynamicsConnectionInterface) {
      return $this->connection->get($key);
    }

    return $this->config->get($key);
  }

  public function getConfigKey() {
    $submission_method = $this->configFactory->get('ace_interface.settings')->get('submission_method');

    switch ($submission_method) {
      case 'submission_api':
        $config = 'ace_submission_framework.settings';
        break;

      default:
        $config = 'ace_dynamics.settings';
    }

    return $config;
  }

  public function refreshToken() {
    $token = $this->getToken();

    if (!is_object($token)) {
      $this->fetchToken();
    }
  }

  public function getToken() {
    $cid = $this->getConfig('resource');
    $token = $this->defaultCache->get($cid);

    \Drupal::logger('getToken check cache')->error(print_r($this->defaultCache->get($cid), 1));

    if (is_object($token) && $token->valid == TRUE) {
      return $token;
    }

    \Drupal::logger('getToken post fetch cache')->error(print_r($this->defaultCache->get($cid), 1));

    return $this->fetchToken();
  }

  public function fetchToken() {
    $resource_key = $this->getConfig('resource_or_scope');
    $body_params = [
      $resource_key => $this->getConfig('resource'),
      'client_id' => $this->getConfig('client_id'),
      'client_secret' => $this->getConfig('client_secret'),
      'grant_type' => $this->getConfig('grant_type')
    ];

    $this->client->base($this->getConfig('authorize_domain'));
    $body = $this->client->buildBody($body_params);

    $headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/x-www-form-urlencoded',
      'Host' => 'login.microsoftonline.com',
      'Expect' => '100-continue',
      'Connection' => 'Keep-Alive',
      'Content-Length' => strlen($body)
    ];

    $options['headers'] = $headers;

    $this->client->base($this->getConfig('authorize_domain'));
    $cid = $this->getConfig('resource');
    $response = $this->client->post($this->getConfig('authorize_endpoint'), $options, $body, $this->getConfig('debug_connection'));
    if ($response) {
      $responseBody = $response->getBody();
      $jsonObject = json_decode($responseBody);
      \Drupal::logger('fetchtoken response body')->error(print_r($jsonObject, 1));

      if (is_object($jsonObject) && property_exists($jsonObject, 'access_token')) {
        $this->defaultCache->set($cid, $jsonObject->access_token, time() + $jsonObject->expires_in);

        return $jsonObject->access_token;
      }
    } else {
        \Drupal::logger('OAuth')->error('Unable to authenticate application with OAuth authentication');
    }

    return 'MissingToken';
  }

  protected function setClient($configFactory) {
    $this->client = new AceGuzzleClient($configFactory);
  }
}

