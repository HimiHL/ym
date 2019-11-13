<?php
namespace App\Service;

use App\Util;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\TransferStats;

class Model
{
    public $domain = 'https://wx.healthych.com';
    public $header = [
        'Host' => 'wx.healthych.com',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Cookie' => '',
        'tk' => '',
        'Connection' => 'keep-alive',
        'Accept' => 'application/json, text/plain, */*',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/4G Language/zh_CN',
        'Referer' => 'https://wx.healthych.com/index.html',
        'st' => '',
        'Accept-Language' => 'zh-cn',
    ];
    public $client;

    /**
     * Miao constructor.
     *
     * @param $cookie Cookie
     * @param $token Token
     * @param $st 未知
     */
    public function __construct($token)
    {
        $this->header['tk'] = $token;
        $this->client = new Client([
            'base_uri' => $this->domain,
            'timeout'  => 999999,
            'headers' => $this->header

        ]);
    }

    /**
     * 获取成都区域的部分医院地区
     *
     * @param bool  $isSeckill
     * @param int   $offset
     * @param int   $limit
     * @param int   $vaccineCode
     * @param int   $regionCode
     *
     * @return mixed
     */
    public function paginate($isSeckill = 0, $vaccineCode = 8803, $regionCode = 5101, $offset = 0, $limit = 10)
    {
        return $this->http('GET', '/base/department/pageList.do', [
            'isSeckill' => $isSeckill,
            'vaccineCode' => $vaccineCode,
            'offset' => $offset,
            'limit' => $limit,
            'regionCode' => $regionCode,
            'isOpen' => 1
        ]);
    }

    /**
     * 获取秒杀详情
     *
     * @param $id
     *
     * @return mixed
     */
    public function vaccine($id)
    {
        return $this->http('GET', '/seckill/vaccine/detail.do', [
            'id' => $id
        ]);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function vaccineDetail($id)
    {
        echo Util::buildTimePrefix("获取秒杀信息\n");
        try {
            return $this->http('GET', '/seckill/vaccine/detailVo.do', [
                'id' => $id
            ]);
        } catch(RequestException $e) {
            echo Util::buildTimePrefix("系统502，重试获取秒杀\n");
            if (502 == $e->getResponse()->getStatusCode()) {
                return $this->vaccineDetail($id);
            }
        }
    }

    /**
     * 提交下单
     *
     * @param $id
     * @param $index
     * @param $memberId
     * @param $date
     * @param $sign
     *
     * @return mixed
     */
    public function submit($id, $index, $memberId, $date, $verifyCode, $sign)
    {
        echo Util::buildTimePrefix("开始提交预约\n");
        try {
            return $this->http('GET', '/seckill/vaccine/subscribe.do', [
                'departmentVaccineId' => $id,
                'vaccineIndex' => $index,
                'linkmanId' => $memberId,
                'subscribeDate' => $date,
                'sign' => $sign,
                'vcode' => $verifyCode
            ], false);
        } catch(RequestException $e) {
            echo Util::buildTimePrefix("系统状态码:{$e->getResponse()->getStatusCode()}，重试预约\n");
            return $this->submit($id, $index, $memberId, $date, $verifyCode, $sign);
        }
    }

    /**
     * 提交下单
     *
     * @param $id
     * @param $index
     * @param $memberID
     * @param $date
     * @param $sign
     *
     * @return mixed
     */
    public function multiSubmit($id, $index, $memberId, $date, $verifyCode, $sign, $total = 20)
    {
        echo Util::buildTimePrefix("开始提交并发预约{$total}次\n");
        $requests = function($total) use($id, $index, $memberId, $date, $sign, $verifyCode) {
            for ($i = 0; $i < $total; $i++) {
                $this->getValidateCode();
                yield new Request('GET', '/seckill/vaccine/subscribe.do?' . http_build_query([
                    'departmentVaccineId' => $id,
                    'vaccineIndex' => $index,
                    'linkmanId' => $memberId,
                    'subscribeDate' => $date,
                    'sign' => $sign,
                    'vcode' => $verifyCode
                ]));
            }
        };

        $pool = new Pool($this->client, $requests($total), [
            'concurrency' => $total,
            'fulfilled' => function($response, $index) {
                echo Util::buildTimePrefix("[索引:{$index}请求完成:{$response->getBody()}\n");
            },
            'rejected' => function($reason, $index) {
                echo Util::buildTimePrefix("[索引:{$index}请求失败:{$reason}\n");
            }
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    public function getVerifyCode()
    {
        return $this->http('GET', '/seckill/validateCode/vcode.do');
    }

    public function workDays($departmentCode, $vaccineCode, $vaccineId,  $linkManId)
    {
        return $this->http('GET', '/order/subscribe/workDays.do', [
            'depaCode' => $departmentCode,
            'linkmanId' => $linkManId,
            'vaccCode' => $vaccineCode,
            'departmentVaccineId' => $vaccineId,
            'vaccIndex' => 1
        ]);
    }

    /**
     * 获取我的联系人列表
     *
     * @return mixed
     */
    public function getMemberList()
    {
        return $this->http('GET', '/order/linkman/findByUserId.do');
    }

    public function region($code)
    {
        return $this->http('GET', '/base/region/childRegions.do', [
            'parentCode' => $code
        ]);
    }

    private function http($method = 'POST', $route, $body = [], $checkResponse = true)
    {
        echo Util::buildTimePrefix("开始请求{$route}\n");
        $result = $this->client->request($method, $route, [
            'verify' => false,
            'query' => $body,
            'on_stats' => function(TransferStats $stats) {
                echo Util::buildTimePrefix("请求结束\n");
                echo Util::buildTimePrefix("请求耗时:". $stats->getTransferTime() ."\n");
            }
        ]);


        $data = json_decode($result->getBody()->getContents(), true);
        if ($checkResponse && $data['code'] !== '0000') {
            throw new Exception($data['msg']);
        }
        return $data;
    }
}
