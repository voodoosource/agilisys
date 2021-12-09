<?php

namespace Drupal\ace_interface\odata;

use Drupal\ace_interface_submission\DynamicsConnectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ace_interface\http\AceTransportClient;


class AceODataClient {

  /**
   * @var \Drupal\ace_interface\http\AceTransportClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $connection;

  /**
   * AceODataClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ace_interface\http\AceTransportClient $client
   */
  public function __construct(ConfigFactoryInterface $config_factory, AceTransportClient $client) {
    $this->configFactory = $config_factory;
    $client->setConfig($this->configFactory->get('ace_dynamics.settings'));
    $this->client = $client;
  }

  public function setClientConnection(DynamicsConnectionInterface $connection) {
    $this->connection = $connection;
    $this->client->setConnection($connection);
  }

  public function setClientConfig($config) {
    $this->client->setConfig($config);
  }

  public function get($uri) {
    return $this->client->get($uri);
  }

  public function post($uri, $data) {
    $body = \GuzzleHttp\json_encode($data);

    $body = str_replace("\/cxm", "/cxm", $body);
    $body = str_replace("\/contacts", "/contacts", $body);

    return $this->client->post($uri, $body);
  }

  public function patch($uri, $data) {
    $body = \GuzzleHttp\json_encode($data);

    $body = str_replace("\/cxm", "/cxm", $body);
    $body = str_replace("\/contacts", "/contacts", $body);

    return $this->client->patch($uri, $body);
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

}
