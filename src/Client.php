<?php
// require __DIR__ . '../../../vendor/autoload.php';

namespace Outkit;
 
class Client
{
    public $key;
    public $secret;
    public $passphrase;
    public $baseUri = 'https://api.outkit.io/v1';

    public function __construct($opts) {
        $this->key = $opts["key"];
        $this->secret = $opts["secret"];
        $this->passphrase = $opts["passphrase"];
        if (array_key_exists('baseUri', $opts)) {
            $this->baseUri = $opts["baseUri"];
        }
    }

    public function getMessage($id) {
        $uri = $this->baseUri . '/messages/' . $id;
        $sigData = $this->getSignatureData('GET', $uri);
        $req = \Httpful\Request::get($uri);
        return $this->finish($req, $sigData);
    }

    public function createMessage($message) {
        $json = json_encode(array("message" => $message));
        $uri = $this->baseUri . '/messages';
        $sigData = $this->getSignatureData('POST', $uri, $json);
        $req = \Httpful\Request::post($uri)
            ->sendsJson()
            ->body($json);
        return $this->finish($req, $sigData);
    }

    private function getSignatureData($method, $uri, $body = '') {
        $uriParts = parse_url($uri);
        $path = $uriParts["path"];
        $qs = array_key_exists('query', $uriParts) ? $uriParts["query"] : '';
        $timestamp = time();
        if ($qs) {
            $body .= '?' + $qs;
        }
        $payload = $timestamp . $method . $path . $body;
        $hmac = hash_hmac('sha256', $payload, $this->secret, true);
        $signature = base64_encode($hmac);
        return array(
            "key" => $this->key,
            "signature" => $signature,
            "timestamp" => $timestamp,
            "passphrase" => $this->passphrase,
        );
    }

    private function finish($req, $sig) {
        $resp = $req
            ->addHeader('User-Agent', 'outkit-php-client')
            ->addHeader('Outkit-Access-Key', $sig["key"])
            ->addHeader('Outkit-Access-Signature', $sig["signature"])
            ->addHeader('Outkit-Access-Timestamp', $sig["timestamp"])
            ->addHeader('Outkit-Access-Passphrase', $sig["passphrase"])
            ->send();
        if ($resp->body->error) {
           throw new \Exception($resp->body->error->code . ': ' . $resp->body->error->message);
        } else {
           return $resp->body->data;
        }
    }


}

?>
