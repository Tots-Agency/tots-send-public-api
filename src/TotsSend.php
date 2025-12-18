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

    /**
     * Send an email
     * 
     * @param string $email
     * @param string $template
     * @param array $params
     * @param array $files Format: [['name' => 'filename.pdf', 'path' => '/path/to/file.pdf']] or [['name' => 'filename.pdf', 'content' => '...']] or [\SplFileInfo]
     * @return mixed
     */
    public function send($email, $template, $params = [], $files = [])
    {
        return $this->generateRequest('POST', 'api/send', [
            'email' => $email,
            'template' => $template,
            'vars' => $params
        ], $files);
    }

    public function sendRaw($email, $subject, $html, $params = [], $plainText = '', $files = [])
    {
        return $this->generateRequest('POST', 'api/send-raw', [
            'email' => $email,
            'subject' => $subject,
            'html' => $html,
            'vars' => $params,
            'plain_text' => $plainText
        ], $files);
    }

    protected function generateRequest($method, $path, $params = null, $files = [])
    {
        // Add API Key
        $params['api_key'] = $this->apiKey;

        // Convert params to multipart
        $multipart = $this->paramsToMultipart($params);

        // Add Files
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof \SplFileInfo) {
                    $multipart[] = [
                        'name' => 'files[]',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getFilename()
                    ];
                } else if (is_array($file) && isset($file['path'], $file['name'])) {
                    $multipart[] = [
                        'name' => 'files[]',
                        'contents' => fopen($file['path'], 'r'),
                        'filename' => $file['name']
                    ];
                } else if (is_array($file) && isset($file['content'], $file['name'])) {
                    $multipart[] = [
                        'name' => 'files[]',
                        'contents' => $file['content'],
                        'filename' => $file['name']
                    ];
                }
            }
        }

        $response = $this->guzzle->request($method, self::BASE_URL . $path, [
            'multipart' => $multipart
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents());
        }

        return null;
    }

    /**
     * Convert params to multipart format
     */
    protected function paramsToMultipart($params, $prefix = '')
    {
        $multipart = [];

        foreach ($params as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '[' . $key . ']';

            if (is_array($value)) {
                $multipart = array_merge($multipart, $this->paramsToMultipart($value, $newKey));
            } else {
                $multipart[] = [
                    'name' => $newKey,
                    'contents' => $value
                ];
            }
        }

        return $multipart;
    }
}
