<?php

namespace Drupal\ace_interface\http;

use Drupal\ace_interface\http\AceGuzzleClientV2;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Psr7\Uri;

class TokenManager {

  const CREDENTIALS = 'ace_token_manager';

  protected $settings;

  protected $defaultCache;

  /**
   * AceOAuthClient constructor.
   *
   * @param Settings $settings
   * @param CacheBackendInterface $defaultCache
   */
  public function __construct(Settings $settings, CacheBackendInterface $defaultCache) {
    $this->settings = $settings;
    $this->defaultCache = $defaultCache;
  }

  public function getResourceToken($scope) {

    $token = $this->getTokenFromCache($scope);

    if ($token != FALSE) {
      return $this->getBearerFormattedToken($token->data);
    }

    $token = $this->fetchToken($scope);

    if ($token == FALSE) {
      return FALSE;
    }

    $this->setCache($scope, $token);

    return $this->getBearerFormattedToken($token->access_token);
  }

  protected function fetchToken($scope) {
    $authDomain = $this->getSetting('auth_domain');
    $authDomainParts = parse_url($authDomain);

    $bodyParams = [
      'scope' => $scope,
      'client_id' => $this->getSetting('client_id'),
      'client_secret' => $this->getSetting('client_secret'),
      'grant_type' => $this->getSetting('grant_type'),
    ];

    $body = http_build_query($bodyParams);

    $authOptions = [
      'body' => $body,
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Host' => $authDomainParts['host'],
        'Expect' => '100-continue',
        'Connection' => 'Keep-Alive',
        'Content-Length' => strlen($body)
      ],
    ];

    $client = new AceGuzzleClientV2(['base_uri' => $authDomain]);

    $authResponse = $client->request(
      'POST',
      new Uri($this->getSetting('auth_endpoint')),
      $authOptions
    );

    if (empty($authResponse)) {
      return FALSE;
    }

    $authBody = $authResponse->getBody();
    $authObject = json_decode($authBody);

    if (is_object($authObject) && property_exists($authObject, 'access_token')) {
      return $authObject;
    }

    return FALSE;
  }

  protected function getTokenFromCache($scope){
    $token = $this->defaultCache->get($scope);

    if (is_object($token) != TRUE) {
      return FALSE;
    }

    if ($token->valid != TRUE) {
      return FALSE;
    }

    return $token;
  }

  protected function setCache($scope, $tokenObject) {
    $this->defaultCache->set($scope, $tokenObject->access_token, time() + $tokenObject->expires_in);
  }

  protected function getBearerFormattedToken($token) {
    return 'Bearer ' . $token;
  }

  protected function getSetting($key) {
    $settings = $this->settings->get(self::CREDENTIALS);

    if (array_key_exists($key, $settings) == FALSE) {
      return FALSE;
    }

    return $settings[$key];
  }
}

