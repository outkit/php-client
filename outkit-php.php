<?php
// Point to where you downloaded the phar
include('./httpful.phar');
 
class Outkit
{
    // property declaration
    public $key;
    public $secret;
    public $passphrase;
    public $baseUri = 'https://api.outkit.io/v1';

    public function __construct($key, $secret, $passphrase, $baseUri) {
      $this->key = $key;
      $this->secret = $secret;
      $this->passphrase = $passphrase;
      if ($baseUri) {
        $this->baseUri = $baseUri;
      }
    }

    // method declaration
    public function getMessage($id) {
      $sigStuff = $this->getSignatureStuff('GET', '/v1/messages/' . $id, '', '');
      $getResp = \Httpful\Request::get($this->baseUri . '/messages/' . $id)
          ->addHeader('Outkit-Access-Key', $sigStuff["key"])
          ->addHeader('Outkit-Access-Signature', $sigStuff["signature"])
          ->addHeader('Outkit-Access-Timestamp', $sigStuff["timestamp"])
          ->addHeader('Outkit-Access-Passphrase', $sigStuff["passphrase"])
          ->send();
      return $getResp->body;
    }

    public function createMessage($message) {
      $json = json_encode(array("message" => $message));
      $sigStuff = $this->getSignatureStuff('POST', '/v1/messages', $json, '');
      $postResp = \Httpful\Request::post($this->baseUri . '/messages')     // Build a POST request...
          ->sendsJson()                            // tell it we're sending (Content-Type) JSON...
          ->body($json)                            // attach a body/payload...
          ->addHeader('Outkit-Access-Key', $sigStuff["key"])
          ->addHeader('Outkit-Access-Signature', $sigStuff["signature"])
          ->addHeader('Outkit-Access-Timestamp', $sigStuff["timestamp"])
          ->addHeader('Outkit-Access-Passphrase', $sigStuff["passphrase"])
          ->send();
      return $postResp->body;
    }

    private function getSignatureStuff($method, $path, $body, $qs) {
      $timestamp = time();
      if ($qs && Object.keys($qs).length !== 0) {
        $body = '?' + querystring.stringify($qs);
      }
      $what = $timestamp . $method . $path . $body;
      $hmac = hash_hmac('sha256', $what, $this->secret, true);
      $signature = base64_encode($hmac);
      return array(
        "key" => $this->key,
        "signature" => $signature,
        "timestamp" => $timestamp,
        "passphrase" => $this->passphrase,
      );
    }
}

?>
