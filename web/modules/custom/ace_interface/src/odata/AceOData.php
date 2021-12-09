<?php

namespace Drupal\ace_interface\odata;

use Drupal\ace_interface_submission\DynamicsConnectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Psr7\Response;

class AceOData {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\ace_interface\odata\AceODataClient
   */
  protected $client;


  protected $config;

  /**
   * @var \Drupal\ace_interface_submission\DynamicsConnectionInterface
   */
  protected $connection;

  /**
   * AceOData constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ace_interface\odata\AceODataClient $client
   */
  public function __construct(ConfigFactoryInterface $config_factory, AceODataClient $client) {
    $this->configFactory = $config_factory;
    $this->client = $client;

    $this->setConfig($this->configFactory->get('ace_dynamics.settings'));
  }

  public function setConnection(DynamicsConnectionInterface $connection) {
    $this->connection = $connection;
    $this->client->setClientConnection($connection);
  }

  public function getConfig($key){
    if ($this->connection instanceof DynamicsConnectionInterface) {
      return $this->connection->get($key);
    }

    return $this->config->get($key);
  }

  public function setConfig($config){
    $this->config = $config;
    $this->client->setClientConfig($config);
  }

  public function query($table, $record = NULL, $fields = NULL, $filters = NULL, $orderby = NULL, array $expand = NULL, $top = NULL) {
    $oDataQuery = new AceODataQuery($table, $record);
    if ($fields) {
      $oDataQuery->fields($fields);
    }
    if ($expand) {
      $oDataQuery->expand($expand);
    }

    if (isset($filters)) {
      if (count($filters) > 0) {

        foreach ($filters as $field => $value) {

          $oDataQuery->filter($field, $value);

        }
      }
    }

    $oDataQuery->order($orderby);
    $oDataQuery->top($top);

    $uri = $this->getConfig('endpoint') . $oDataQuery->buildQuery();

    $response = $this->get($uri);

    if ($response) {
      $response_body = $response->getBody();
      $json = json_decode($response_body);
      return $json;
    }
    else {
      return NULL;
    }

  }

  public function insert($table, $data) {
    $uri = $this->getConfig('endpoint') . $table;
    return $this->post($uri, $data);
  }

  public function update($table, $key, $data) {
    $uri = $this->getConfig('endpoint') . $table . '(' . $key . ')';
    return $this->patch($uri, $data);
  }

  public function post($uri, $body) {
    $response = $this->client->post($uri, $body);
    return $response;
  }

  public function patch($uri, $body) {
    $response = $this->client->patch($uri, $body);
    return $response;
  }

  public function get($uri) {
    $response = $this->client->get($uri);
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

  public function extractID($haystack) {
    $start = strpos($haystack, '(');
    $start++; //Move past the bracket

    $finish = strpos($haystack, ')');
    $length = $finish - $start;
    $guid = substr($haystack, $start, $length);

    return $guid;
  }

}
