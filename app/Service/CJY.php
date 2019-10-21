<?php
namespace App\Service;

use Exception;
use GuzzleHttp\Client;

class CJY
{
    public $domain = 'https://upload.chaojiying.net';

    /**
     * Miao constructor.
     *
     * @param $cookie Cookie
     * @param $token Token
     * @param $st 未知
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->domain,
            'timeout'  => 999999
        ]);
    }

    /**
     * 获取我的联系人列表
     *
     * @return mixed
     */
    public function process($content)
    {
        return $this->http('POST', '/Upload/Processing.php', [
            'user' => getenv('CJY_USER'),
            'pass' => getenv('CJY_PASS'),
            'softid' => getenv('CJY_ID'),
            'codetype' => '6001',
            'file_base64' => $content
        ]);
    }

    private function http($method = 'POST', $route, $body = [])
    {
        $result = $this->client->request($method, $route, [
            'verify' => false,
            'form_params' => $body
        ]);

        $data = json_decode($result->getBody()->getContents(), true);
        
        return $data;
    }
}
