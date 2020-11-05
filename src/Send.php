<?php

namespace Fedirum\Fedirum;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
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
        $this->privateKey = file_get_contents(__DIR__ . Config::PRIVATE_PEM);
        $this->publicKey = file_get_contents(__DIR__ . Config::PUBLIC_PEM);
    }

    public function getPublicKey() {
        return $this->publicKey;
    }

    public function send($user, $content, $inbox): Request {
        $date = gmdate('D, d M Y H:i:s \G\M\T', time());
        $sign = "(request-target): post /inbox\nhost: mastodon.social\ndate: " . $date;
        openssl_sign($sign, $binary_signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($binary_signature);
        $header = 'keyId="' . $this->getActorLink($user) . '",headers="(request-target) host date",signature="' . $signature . '"';
	$request = new Request('POST', $inbox, array(
            'Host' => 'mastodon.social',
            'Date' => $date,
	    'Signature' => $header,
	    'Accept' => 'application/activity+json'
	), $content);
        return $request;
    }
}
