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
     * @param int   $offset
     * @param int   $limit
     * @param int   $vaccineCode
     * @param int   $regionCode
     * @param int   $isOpen
     * @param float $longitude
     * @param float $latitude
     *
     * @return mixed
     */
    public function paginate($offset = 0, $limit = 10,$vaccineCode = 8803, $regionCode = 5101, $isOpen = 1, $longitude = '104.06520080566406', $latitude = '30.54224395751953')
    {
        return $this->http('GET', '/base/department/pageList.do', [
            'vaccineCode' => $vaccineCode,
            'cityName' => '',
            'offset' => $offset,
            'limit' => $limit,
            'name' => '',
            'regionCode' => $regionCode,
            'isOpen' => $isOpen,
            'longitude' => $longitude,
            'latitude' => $latitude,
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
        return $this->http('GET', '/seckill/vaccine/detailVo.do', [
            'id' => $id
        ]);
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
    public function submit($id, $index, $memberID, $date, $sign, $verifyCode)
    {
        echo "提交预约一次-".date('YmdHis')."\n";
        try {
            return $this->http('GET', '/seckill/vaccine/subscribe.do', [
                'departmentVaccineId' => $id,
                'vaccineIndex' => $index,
                'linkmanId' => $memberID,
                'subscribeDate' => $date,
                'sign' => $sign,
                'vcode' => $verifyCode
            ]);
        } catch(RequestException $e) {
            echo "秒杀系统502，再重试一次-".date('YmdHis')."\n";
            if (502 == $e->getResponse()->getStatusCode()) {
                return $this->submit($id, $index, $memberID, $date, $sign, $verifyCode);
            }
        }
    }

    public function getVerifyCode()
    {
        return $this->http('GET', '/seckill/validateCode/vcode.do');
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

    private function http($method = 'POST', $route, $body = [])
    {
        $result = $this->client->request($method, $route, [
            'verify' => false,
            'headers' => $this->header,
            'query' => $body
        ]);

        $data = json_decode($result->getBody()->getContents(), true);
        if ($data['code'] !== '0000') {
            throw new Exception($data['msg']);
        }
        return $data;
    }
}
