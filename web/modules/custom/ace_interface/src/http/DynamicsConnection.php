<?php


namespace Drupal\ace_interface;


class DynamicsConnection implements DynamicsConnectionInterface {

  protected $credentials;

  /**
   * DynamicsConnection constructor.
   * @param $credentials
   */
  public function __construct(array $credentials) {
    $this->credentials = $credentials;
  }

  public function get($key) {
    if (array_key_exists($key, $this->credentials) == FALSE) {
      return FALSE;
    }

    return $this->credentials[$key];
  }

}

