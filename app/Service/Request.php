<?php
namespace App\Service;

use App\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;
use GuzzleHttp\TransferStats;

class Request
{
    public $domain = 'https://wx.healthych.com';
    public $header = [
        'Host' => 'wx.healthych.com',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Cookie' => 'UM_distinctid=16e1f707fb2ab-0f3389883b93a8-f044405-38400-16e1f707fb918; __SDID=37d442ed6319f388; _xzkj_=45a45b6066d147243c0e03295abd879d_923fdbdcb168d5960d16dbbf17f186d7; CNZZDATA1261985103=1507093689-1572482047-%7C1572848441; _xxhm_=%7B%22address%22%3A%22%22%2C%22awardPoints%22%3A0%2C%22birthday%22%3A868032000000%2C%22createTime%22%3A1566004028000%2C%22headerImg%22%3A%22http%3A%2F%2Fthirdwx.qlogo.cn%2Fmmopen%2FoCVIF9sBEcpGwickWibIRvKpsEbsKdt04aEFxSoRkBlln6PbpNTaUI7qUUIDSZuozL9CeFYFGOFBCP7WEzEacZEod3gZA77ibZic%2F132%22%2C%22id%22%3A3435493%2C%22idCardNo%22%3A%22513424199707050420%22%2C%22isRegisterHistory%22%3A0%2C%22latitude%22%3A30.548683%2C%22longitude%22%3A104.058884%2C%22mobile%22%3A%2218111630102%22%2C%22modifyTime%22%3A1574663917000%2C%22name%22%3A%22%E5%88%98%E7%A7%91%E8%8E%89%22%2C%22nickName%22%3A%22HaLi%22%2C%22openId%22%3A%22og46NxNpYO5LgmikTvC8lnOUbzMo%22%2C%22regionCode%22%3A%22510109%22%2C%22registerTime%22%3A1566059582000%2C%22sex%22%3A2%2C%22source%22%3A1%2C%22uFrom%22%3A%22cdbdbsy%22%2C%22unionid%22%3A%22o8NLkwYul__l8ttj0GY_iri9-iR8%22%2C%22wxSubscribed%22%3A1%2C%22yn%22%3A1%7D',
        'tk' => '',
        'Connection' => 'keep-alive',
        'Accept' => 'application/json, text/plain, */*',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 MicroMessenger/7.0.8(0x17000820) NetType/4G Language/zh_CN',
        'Referer' => 'https://wx.healthych.com/index.html',
        'st' => '47d866523e30616aca1b4a6c221758a2',
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
        $result = $this->findUser();
        $this->header['Cookie'] = $this->buildCookie($result['data'], $token);
    }

    private function buildCookie($data, $tk)
    {
        $headers = [
            'UM_distinctid' => '16e1f707fb2ab-0f3389883b93a8-f044405-38400-16e1f707fb918',
            '__SDID' => '37d442ed6319f388',
            '_xzkj_' => $tk,
            'CNZZDATA1261985103' => '1507093689-1572482047-%7C1572848441',
            '_xxhm_' => urlencode(json_encode($data))
        ];
        $header = '';
        foreach ($headers as $key => $header) {
            $header .= "{$key}={$header}; ";
        }
        return $header;
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

    public function findUser()
    {
        return $this->http('GET', '/passport/user/findLoginUserByKey.do',[]);
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
            ], false);
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
                yield new GuzzleHttpRequest('GET', '/seckill/vaccine/subscribe.do?' . http_build_query([
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
        try {
            return $this->http('GET', '/seckill/validateCode/vcode.do');
        } catch(RequestException $e) {
            echo Util::buildTimePrefix("系统状态码:{$e->getResponse()->getStatusCode()}，重试获取验证码\n");
            return $this->http('GET', '/seckill/validateCode/vcode.do');
        }
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

    public function checkToken()
    {
        return $this->http('GET', '/base/region/childRegions.do', [
            'parentCode' => 0
        ], false);
    }

    private function http($method = 'POST', $route, $body = [], $checkResponse = true)
    {
        // echo Util::buildTimePrefix("开始请求{$route}\n");
        $result = $this->client->request($method, $route, [
            'verify' => false,
            'query' => $body,
            'on_stats' => function(TransferStats $stats) {
                // echo Util::buildTimePrefix("请求结束\n");
                // echo Util::buildTimePrefix("请求耗时:". $stats->getTransferTime() ."\n");
            }
        ]);


        $data = json_decode($result->getBody()->getContents(), true);
        if ($checkResponse && $data['code'] !== '0000') {
            throw new \Exception($data['msg']);
        }
        return $data;
    }
}
