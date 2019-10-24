<?php
namespace App\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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

    /**
     * Miao constructor.
     *
     * @param $cookie Cookie
     * @param $token Token
     * @param $st 未知
     */
    public function __construct($cookie, $token, $st)
    {
        $this->header['Cookie'] = $cookie;
        $this->header['tk'] = $token;
        $this->header['st'] = $st;
        $this->client = new Client([
            'base_uri' => $this->domain,
            'timeout'  => 999999
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
        echo "获取一次秒杀信息-".date('YmdHis')."\n";
        try {
            return $this->http('GET', '/seckill/vaccine/detailVo.do', [
                'id' => $id
            ]);
        } catch(RequestException $e) {
            echo "502，再重试一次-".date('YmdHis')."\n";
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
     * @param $memberID
     * @param $date
     * @param $sign
     *
     * @return mixed
     */
    public function submit($id, $index, $memberID, $date, $verifyCode)
    {
        echo "提交预约一次\n";
        try {
            return $this->http('GET', '/seckill/vaccine/subscribe.do', [
                'departmentVaccineId' => $id,
                'vaccineIndex' => $index,
                'linkmanId' => $memberID,
                'subscribeDate' => $date,
                // 'sign' => $sign,
                'vcode' => $verifyCode
            ]);
        } catch(RequestException $e) {
            echo "秒杀系统502，再重试一次\n";
            if (502 == $e->getResponse()->getStatusCode()) {
                return $this->submit($id, $index, $memberID, $date, $verifyCode);
            }
        }
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

    private function http($method = 'POST', $route, $body = [], $checkResponse = true)
    {
        $result = $this->client->request($method, $route, [
            'verify' => false,
            'headers' => $this->header,
            'query' => $body
        ]);

        $data = json_decode($result->getBody()->getContents(), true);
        if ($checkResponse && $data['code'] !== '0000') {
            throw new Exception($data['msg']);
        }
        return $data;
    }
}
