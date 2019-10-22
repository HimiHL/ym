<?php
namespace App\Service;

use Exception;

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
    public function fixedSubmit($id, $memberId, $verifyCode, $sign, $date)
    {
        $index = 0; //不知道含义
        return $this->model->submit($id, $index, $memberId, $date, $sign, $verifyCode);
    }

    /**
     * 秒杀
     * 
     */
    public function submit($id, $memberId, $verifyCode, $detail)
    {
        $sign = md5($detail['time'] ?? 0 . 'healthych.com');
        $workDays = $detail['days'] ?? []; // 工作日列表
        $freeDay = [
            'day' => $workDays[0]['day'] ?? '20191025',
            'total' => 1
        ];
        foreach ($workDays as $workDay) {
            if ($workDay['total'] > 0) {
                $freeDay = $workDay;
            }
        }
        $date = date('Y-m-d', strtotime($freeDay['day']));// YYYY-mm-dd
        $index = 1; //不知道含义

        return $this->model->submit($id, $index, $memberId, $date, $sign, $verifyCode);
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
}