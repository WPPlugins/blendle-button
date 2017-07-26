<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class PayException extends Exception {}
class PayFailedRequestException extends PayException {}
class PayInvalidInputException extends PayException {}

class PaySDK {
  private $provider_uid;
  private $api_secret;
  private $public_key;

  function __construct($provider_uid, $public_key, $api_secret, $production = false) {
    if (empty($provider_uid)) {
      throw new InvalidArgumentException('missing provider_uid');
    }

    if (empty($public_key)) {
      throw new InvalidArgumentException('missing public_key');
    }

    if (empty($api_secret)) {
      throw new InvalidArgumentException('missing api_secret');
    }

    $this->provider_uid = $provider_uid;
    $this->public_key = $public_key;
    $this->api_secret = $api_secret;
    $this->production = $production;
  }

  public function acquiredItem($jwt) {
    try {
      $decoded = JWT::decode($jwt, $this->public_key, array('RS256'));

      if($decoded->data && $decoded->data->acquired) {
        return isset($decoded->data->foreign_uid) ? $decoded->data->foreign_uid : $decoded->data->item_uid;
      } else {
        return null;
      }
    } catch (UnexpectedValueException $e) {
      return null;
    } catch (DomainException $e) {
      return null;
    }
  }

  public function hasSubscription($jwt) {
    try {
      $decoded = JWT::decode($jwt, $this->public_key, array('RS256'));

      return $decoded->data && $decoded->data->subscription && $decoded->aud == $this->provider_uid &&
             $decoded->sub === 'subscription' && $decoded->data->provider_uid == $this->provider_uid;
    } catch (UnexpectedValueException $e) {
      return false;
    } catch (DomainException $e) {
      return false;
    }
  }

  public function registerItem($url, $options = array()) {
    $api_url = $this->apiURL() . join('/', array('provider', $this->provider_uid, 'items'));
    $json = array_merge(array('url' => $url), $options);
    return $this->postJSON($api_url, $json)->uid;
  }

  public function updateAttributes($item_uid, $options = array()) {
    $api_url = $this->apiURL() . join('/', array('item', $item_uid, 'metadata'));
    return !is_null($this->postJSON($api_url, $options));
  }

  public function clientURL() {
    $client_url = getenv('PAY_CLIENTJS_URL');

    if (empty($client_url)) {
      $client_url = $this->production ? 'https://pay.blendle.com/client/js/client.js' : 'https://pay.blendle.io/client/js/client.js';
    }

    return $client_url;
  }

  public function apiURL() {
    $api_url = getenv('PAY_API_URL');

    if (empty($api_url)) {
      $api_url = $this->production ? 'https://pay.blendle.com/api/' : 'https://pay.blendle.io/api/';
    }

    return $api_url;
  }

  public function getItemJwt($item_uid, $metadata = array()) {
    $metadata['foreign_uid'] = $item_uid;

    return JWT::encode(array(
      'data' => $metadata,
      'iss' => $this->provider_uid,
      'sub' => 'item',
      'iat' => time()
    ), $this->api_secret);
  }

  private function sendPayload($method, $url, $json) {
    $encoded_json = json_encode($json);

    if(!$encoded_json || json_last_error() != JSON_ERROR_NONE) {
      throw new PayInvalidInputException('Cannot encode JSON, it could be that the data is incorrectly encoded');
    }

    $params = array(
      'http' => array(
        'method' => $method,
        'header'=> $this->rawHeader(),
        'content' => $encoded_json,
        'ignore_errors' => true,
        'timeout' => 120
      )
    );

    $ctx = stream_context_create($params);
    $fp = fopen($url, 'rb', false, $ctx);

    $headers = $this->parseHeaders($http_response_header);

    $response = json_decode(stream_get_contents($fp));

    if($headers['status_code'] >= 400) {
      $message = 'status code: ' . $headers['status_code'];
      if ($response->_errors) {
        $error = $response->_errors[0];
        $message .= ', type: ' . $error->id . ', message: ' . $error->message;
        if(property_exists($error, 'logref')) {
          $message .= ', logref: ' . $error->logref;
        }
      }
      throw new PayFailedRequestException($message, $headers['status_code']);
    }

    return $response;
  }

  private function postJSON($url, $json) {
    return $this->sendPayload('POST', $url, $json);
  }

  private function parseHeaders($headers) {
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['status_code'] = intval($out[1]);
        }
    }
    return $head;
  }

  private function headers() {
    $headers = array(
      'User-Agent' => 'sdk-php; d8d39ac7519ec1bb60b17cb8de30a28756fa1473',
      'Content-Type' => 'application/json'
    );

    if ($this->api_secret) {
      $headers['Authorization'] = 'Token ' . $this->api_secret;
    }

    return $headers;
  }

  private function rawHeader() {
    $header = "";

    foreach($this->headers() as $key => $value)  {
      $header .= "$key: $value\r\n";
    }

    return $header;
  }
}
