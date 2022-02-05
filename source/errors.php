<?php
class IPAddressBlockedException extends Exception {
  protected $message = "Your IP address is blacklisted by bonbast.com";
  protected $code = 403;
}

class InvalidHTTPStatusException extends Exception {
  protected $message = "The bonbast.com returned invalid HTTP status code";

  function __construct($code) {
    $this->code = $code;
  }
}

class BadHomepageDataException extends Exception {
  protected $message = "The bonbast.com website returned unsupported homepage response"
    . PHP_EOL . "Please fill an issue in https://github.com/BaseMax/bonbast-api/issues/new";
  protected $code = 500;
}

class InvalidApiKeyException extends Exception {
  protected $message = "The bonbast.com API returned 'reset' field in response, which means the key is invalid!";
  protected $code = 500;
}