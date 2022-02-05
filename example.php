<?php
// Max Base
// https://github.com/BaseMax/bonbast-api
require_once "source/bonbast-api.php";

try {
  $api = new BonBast();
  $prices = $api->fetchPrices();

  // Display JSON
  echo json_encode($prices) . PHP_EOL;
} catch (IPAddressBlockedException $e) {
  die($e->getMessage());
} catch (InvalidHTTPStatusException $e) {
  die($e->getMessage());
} catch (BadHomepageDataException $e) {
  die($e->getMessage());
} catch (InvalidApiKeyException $e) {
  die($e->getMessage());
} catch (Exception $e) {
  die("Invalid exception: " . $e->getMessage());
}
