<?php

namespace TotsSend;

use GuzzleHttp\Psr7\Request;

class TotsSend 
{
    /**
     * URL de la API
     */
    const BASE_URL = 'https://api.send.tots.agency/';
    /**
     *
     * @var string
     */
    protected $apiKey = '';
    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->guzzle = new \GuzzleHttp\Client();
    }

    public function send($email, $template, $params = [])
    {
        return $this->generateRequest('POST', 'api/send', [
            'email' => $email,
            'template' => $template,
            'vars' => $params
        ]);
    }

    public function sendRaw($email, $subject, $html, $params = [], $plainText = '')
    {
        return $this->generateRequest('POST', 'api/send-raw', [
            'email' => $email,
            'subject' => $subject,
            'html' => $html,
            'vars' => $params,
            'plain_text' => $plainText
        ]);
    }

    protected function generateRequest($method, $path, $params = null)
    {
        $body = null;
        if($params != null){
            $params['api_key'] = $this->apiKey;
            $body = json_encode($params);
        }

        $request = new Request(
            $method, 
            self::BASE_URL . $path, 
            [
                'Content-Type' => 'application/json',
            ], $body);

        $response = $this->guzzle->send($request);
        if($response->getStatusCode() == 200){
            return json_decode($response->getBody()->getContents());
        }

        return null;
    }
}
