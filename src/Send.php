<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Send {
    private $privateKey;
    private $publicKey;
    protected function getBaseLink(): string {
        return 'https://' . $_SERVER['SERVER_NAME'];
    }
    protected function getActorLink($username): string {
        return $this->getBaseLink() . Config::ACTOR_PATH . $username;
    }

    function __construct() {
        $this->privateKey = file_get_contents(__DIR__ . '/' . Config::PRIVATE_PEM);
        $this->publicKey = file_get_contents(__DIR__ . '/' . Config::PUBLIC_PEM);
    }

    public function getPublicKey() {
        return $this->publicKey;
    }

    public function retrievePublicKey($keyId) {
        $json = json_decode($this->get($keyId));
        $publicKey = $json['publicKey'];
        if($publicKey['id'] == $keyId) {
            return $publicKey['publicKeyPem'];
        } else {
            error_log('owner not match');
            return null;
        }
    }

    public function verify(ServerRequestInterface $request) {
        $headers = $request->getHeaders();
        $signature = $headers['signature'][0];
        $fields = [];
        if($signature) {
            foreach(explode(',', $signature) as $field) {
                $pair = explode('=', $field, 2);
                if(count($pair) == 2) {
                    $fields[$pair[0]] = trim($pair[1], ' "');
                }
            }
            error_log(var_export($fields, TRUE));
            if(!array_key_exists('headers', $fields)) {
                $fields['headers'] = 'date';
            }

            $signs = [];
            foreach(explode(' ', $fields['headers']) as $key) {
                if($key == '(request-target)') {
                    $signs[] = '(request-target): post ' . Config::INBOX_PATH;
                } else {
                    if($key == 'host' && !$headers[$key]) {
                        $signs[] = $_SERVER['SERVER_NAME'];
                    } else {
                        if(is_array($headers[$key])) {
                            $signs[] = $headers[$key][0];
                        } else {
                            $signs[] = $headers[$key];
                        }
                    }
                }
            }
            $sign = implode("\n", $signs);

            error_log($sign);            
            $publicKey = $this->retrievePublicKey($fields['keyId']);
            # TODO: use dynamic algorithms depending on $fields['algorithm']
            $ok = openssl_verify($sign, base64_decode($fields['signature']), $publicKey, "sha256WithRSAEncryption");
            error_log('Verification: ' . $ok);
        }
        return false;
    }

    public function post($user, $content, $inbox): Response {
        $host = parse_url($inbox, PHP_URL_HOST);
        
        $date = gmdate('D, d M Y H:i:s \G\M\T', time());
        $sign = "(request-target): post /inbox\nhost: " . $host . "\ndate: " . $date;
        openssl_sign($sign, $binary_signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($binary_signature);
        $header = 'keyId="' . $this->getActorLink($user) . '",headers="(request-target) host date",signature="' . $signature . '"';
        $request = new Request('POST', $inbox, array(
            'Host' => $host,
            'Date' => $date,
            'Signature' => $header,
            'Accept' => 'application/activity+json'
        ), $content);
        
        $client = new Client(array(
            # This base_uri won't bother.
            'base_uri' => 'https://mastodon.social',
            'timeout' => 2.0
        ));
        error_log($content);
        return $client->send($request);
    }

    public function get($link): Response {
        $host = parse_url($link, PHP_URL_HOST);
        $request = new Request('GET', $link, array(
            'Host' => $host,
            'Accept' => 'application/activity+json'
        ), '');
        
        $client = new Client(array(
            # This base_uri won't bother.
            'base_uri' => 'https://mastodon.social',
            'timeout' => 2.0
        ));
        return $client->send($request);
    }
}
