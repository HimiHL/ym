<?php
namespace App\Service;

use App\Util;
use Exception;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class Handle
{
    public $model;
    public function __construct()
    {
        $cookie = getenv('COOKIE');
        if (!$cookie) {
            throw new Exception('请配置COOKIE');
        }
        $token = getenv('TK');
        if (!$token) {
            throw new Exception('请配置TK');
        }
        $st = getenv('ST');
        if (!$st) {
            throw new Exception('请配置ST');
        }
        $this->model = new Model($cookie, $token, $st);
    }

    /**
     * 获取所有可秒杀的医院列表
     * 
     */
    public function getVaccines($isSeckill = 1, $vaccineCode = 8803, $regionCode = 5101)
    {
        $offset = 0;
        $list = [];
        do {
            $response = $this->model->paginate($isSeckill, $vaccineCode, $regionCode, $offset);
            if ($response['code'] == '0000') {
                if ($offset > $response['data']['total']) {
                    break;
                }
                $offset += 10;//$response['data']['end'];

                foreach ($response['data']['rows'] as $row) {
                    // 如果可以秒杀
                    if ($row['isSeckill'] == 1) {
                        foreach ($row['vaccines'] as $key => $value) {
                            $vaccine = $this->model->vaccine($value['id']);
                            if ($vaccine['code'] == '0000') {
                                $row['vaccines'][$key]['vaccine'] = $vaccine['data'];
                            }
                        }
                        $list[] = $row;
                    }
                }

            } else {
                break;
            }
        } while($offset > 0);

        return $list;
    }

    /**
     * 获取秒杀详情
     * 
     */
    public function vaccineDetail($id)
    {
        $result = $this->model->vaccineDetail($id);
        if ($result['code'] == '0000') {
            return $result['data'];
        }
        throw new Exception($result['msg']);
    }

    /**
     * 秒杀
     * 
     */
    public function fixedSubmit($id, $memberId, $verifyCode, $date, $sign)
    {
        $index = 0; //不知道含义
        return $this->model->submit($id, $index, $memberId, $date, $verifyCode, $sign);
    }

    /**
     * 获取身份列表
     *
     */
    public function getMemberList($idCard = '')
    {
        $result = $this->model->getMemberList();
        if ($result['code'] == '0000') {
            if ($idCard != '') {
                foreach ($result['data'] as $member) {
                    if ($member['idCardNo'] == $idCard) {
                        return $member;
                    }
                }
            }
            return $result['data'];
        }
        return [];
    }


    /**
     * 获取验证码
     * 
     */
    public function getValidateCode()
    {
        $result = $this->model->getVerifyCode();
        if ($result['code'] == '0000') {
            return $result['data'];
        }
        throw new Exception($result['msg']);
    }

    public function getWorkDays($departmentCode, $vaccineCode, $vaccineId, $memberId)
    {
        $workDays = $this->model->workDays($departmentCode, $vaccineCode, $vaccineId, $memberId);
        return $workDays['data'] ?? [];
    }

    public function getRegions($code = 0)
    {
        $regions = $this->model->region($code);
        return $regions['data'] ?? [];
    }

    public function multiSubmit($id, $memberId, $date, $sign, $total = 10)
    {
        $header = $this->model->header;
        $client = $this->model->client;
        $route = '/seckill/vaccine/subscribe.do?';

        $requests = function($total) use($route, $header, $id, $memberId, $date, $sign) {
            for ($i = 0; $i < $total; $i++) {
                $this->getValidateCode();
                for ($j = 0; $j < 100; $j++) {
                    yield new Request('GET', $route . http_build_query([
                        'departmentVaccineId' => $id,
                        'vaccineIndex' => 1,
                        'linkmanId' => $memberId,
                        'subscribeDate' => $date,
                        'sign' => $sign,
                        'vcode' => $j
                    ]), $header);
                }
            }
        };

        $pool = new Pool($client, $requests($total), [
            'concurrency' => 100,
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
}