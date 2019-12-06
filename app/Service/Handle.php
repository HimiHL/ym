<?php
namespace App\Service;

use App\Service\Request;

class Handle
{
    public $model;
    public function __construct($token)
    {
        $this->model = new Request($token);
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
                $offset += 10;

                foreach ($response['data']['rows'] as $row) {
                    // 如果可以秒杀
                    if ($row['isSeckill'] == 1) {
                        foreach ($row['vaccines'] as $key => $value) {
                            try {
                                $vaccine = $this->model->vaccine($value['id']);
                                if ($vaccine['code'] == '0000') {
                                    $row['vaccines'][$key]['vaccine'] = $vaccine['data'];
                                }
                            } catch(\Exception $e) {

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
        throw new \Exception($result['msg'], 5555);
    }

    /**
     * 获取秒杀详情
     * 返回包含code的结果
     */
    public function moreTimesVaccineDetail($id)
    {
        return $this->model->vaccineDetail($id);
    }

    /**
     * 秒杀
     * 
     */
    public function submit($id, $memberId, $verifyCode, $date, $sign)
    {
        $index = 1; //不知道含义
        return $this->model->submit($id, $index, $memberId, $date, $verifyCode, $sign);
    }

    /**
     * 并发秒杀
     * 
     */
    public function multiSubmit($id, $memberId, $verifyCode, $date, $sign)
    {
        $index = 1; //不知道含义
        return $this->model->multiSubmit($id, $index, $memberId, $date, $verifyCode, $sign);
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
        throw new \Exception($result['msg']);
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

    public function checkToken()
    {
        $response = $this->model->checkToken();
        if ($response['code'] == '3101') {
            throw new \Exception($response['msg'], 3101);
        }
    }
}