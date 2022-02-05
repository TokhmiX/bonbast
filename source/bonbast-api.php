<?php
/*
 * @Name: bonbast-api
 * @Author: Max Base
 * @Repository: https://github.com/BaseMax/bonbast-api
 * @Date: Jul 15, 2020, 2020-08-07, 2021-08-01, 2021-08-02, 2021-08-10
 */
require_once "errors.php";

class BonBast
{
  private $baseURI = "https://www.bonbast.com";

  private $user_agent = "Mozilla/5.0(Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36(KHTML,like Gecko) curlrome/68.0.3440.106 Mobile Safari/537.36";

  /**
   * How the response structure should be mapped
   * Before returning it.
   */
  private $map = [
    // Currency name, Sell key, Buy key
    ["US Dollar", "usd1", "usd2"],
    ["Euro", "eur1", "eur2"],
    ["British Pound", "gbp1", "gbp2"],
    ["Swiss Franc", "chf1", "chf2"],
    ["Canadian Dollar", "cad1", "cad2"],
    ["Australian Dollar", "aud1", "aud2"],
    ["Swedish Krona", "sek1", "sek2"],
    ["Norwegian Krone", "nok1", "nok2"],
    ["Russian Ruble", "rub1", "rub2"],
    ["Thai Baht", "thb1", "thb2"],
    ["Singapore Dollar", "sgd1", "sgd2"],
    ["Hong Kong Dollar", "hkd1", "hkd2"],
    ["Azerbaijani Manat", "azn1", "azn2"],
    ["Armenian Dram", "amd1", "amd2"],
    ["Danish Krone", "dkk1", "dkk2"],
    ["UAE Dirham", "aed1", "aed2"],
    ["Japanese Yen", "jpy1", "jpy2"],
    ["Turkish Lira", "try1", "try2"],
    ["Chinese Yuan", "cny1", "cny2"],
    ["KSA Riyal", "sar1", "sar2"],
    ["Indian Rupee", "inr1", "inr2"],
    ["Ringgit", "myr1", "myr2"],
    ["Afghan Afghani", "afn1", "afn2"],
    ["Kuwaiti Dinar", "kwd1", "kwd2"],
    ["Iraqi Dinar", "iqd1", "iqd2"],
    ["Bahraini Dinar", "bhd1", "bhd2"],
    ["Omani Rial", "omr1", "omr2"],
    ["Qatari Riyal", "qar1", "qar2"],

    // Coin name, Sell key, Buy key.
    ["Azadi", "azadi1", "azadi12"],
    ["Emami", "emami1", "emami12"],
    ["½ Azadi", "azadi1_2", "azadi1_22"],
    ["¼ Azadi", "azadi1_4", "azadi1_42"],
    ["Gerami", "azadi1g", "azadi1g2"],

    // Gold name, Sell key
    ["Gold Gram", "gol18"],
    ["Gold Mithqal", "mithqal"],
    ["Gold Ounce", "ounce"],

    // Digital currency name, Sell key
    ["Bitcoin", "bitcoin"],
  ];

  function fetchPrices()
  {
    $homepage = $this->requestFetchHomePage();
    $key = $this->extractKeyFromHomePage($homepage);
    $json_object = $this->requestFetchPrices($key);
    return $this->mapResponse($json_object);
  }

  /**
   * Send HTTP Request to fetch homepage
   */
  private function requestFetchHomePage()
  {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_RETURNTRANSFER => true, // return body
      CURLOPT_HEADER => true, // return status code
      CURLOPT_URL => $this->baseURI,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HTTPHEADER => ["user-agent: " . $this->user_agent],
    ]);
    $homepage = curl_exec($curl);
    $HTTPCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $this->validateHTTPStatusCode($HTTPCode);

    return $homepage;
  }

  /**
   * Extract key from homepage response
   */
  private function extractKeyFromHomePage($homepage)
  {
    /**
     * Extract "body parameters" which are hard coded in homepage response
     */
    preg_match('/data\:"(.*?)"/s', $homepage, $matches);

    // Couldn't extract the API key from homepage source.
    if (!isset($matches[1])) throw new BadHomepageDataException();
    return $matches[1];
  }

  /**
   * Send HTTP Request to fetch prices via bonbast.com API
   */
  private function requestFetchPrices($key)
  {
    /**
     * Request to get prices
     */
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_RETURNTRANSFER => true, // return body
      CURLOPT_HEADER => true, // return status code
      CURLOPT_URL => $this->baseURI . "/json",
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_POSTFIELDS => "data=" . $key . "&webdriver=false",
      CURLOPT_HTTPHEADER => [
        "user-agent: " . $this->user_agent,
        "content-type: application/x-www-form-urlencoded; charset=UTF-8",
        "referer: " . $this->baseURI . "/",
        "Cookie: st_bb=0",
      ],
    ]);
    $response = curl_exec($curl);
    $HTTPCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $this->validateHTTPStatusCode($HTTPCode);

    // Then, after your curl_exec call:
    $body = substr($response, curl_getinfo($curl, CURLINFO_HEADER_SIZE));

    // JSON decode response body
    $json_object = @json_decode($body, true);

    /**
     * Check if the decoded JSON is empty
     */
    if ($json_object === null) throw new Exception("Couldn't decode JSON response");

    /**
     * If decoded JSON has "reset" field inside, means API request has failed.
     */
    if (isset($json_object["reset"])) throw new InvalidApiKeyException();

    return $json_object;
  }

  private function mapResponse($json_object)
  {
    $prices = [];

    for ($i = 0; $i < count($this->map); $i++) {
      $name = $this->map[$i][0];
      $sell_key = $this->map[$i][1];
      $prices[$name] = [
        "sell" => $json_object[$sell_key],
      ];

      // Check if this price name, has the "buy" field in map variable
      if (isset($this->map[$i][2])) {
        $buy_key = $this->map[$i][2];
        $prices[$name]["buy"] = $json_object[$buy_key];
      } else {
        // This currency doesn't have "buy" key,
        // Uncomment the line below to set default 0 value
        // $prices[$name]["buy"] = 0;
      }
    }

    return $prices;
  }

  private function validateHTTPStatusCode($code)
  {
    if (!in_array($code, [200, 201])) {
      switch ($code) {
        case 403:
          throw new IPAddressBlockedException();

        default:
          throw new InvalidHTTPStatusException($code);
      }
    }
  }
}
