<?php
namespace App\Console;

use App\Service\CJY;
use App\Service\Handle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NBYueMiao extends Command
{
    protected $name = 'nbym';
    protected $description = '流程式约苗预约[耗时太长，建议在秒杀活动1分钟前开启]';
    protected $requireArgument = [
    ];
    protected $optionalArgument = [
    ];
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $miao = new Handle;
        // 设置医院预约信息，获取秒杀开始时间
        $vaccineList = $miao->getVaccines();
        $vaccineIndex = explode('-', $this->chooseAsk('请选择一个预约医院【输入序号】', array_map(function($value, $key) {
            return sprintf('%s-%s[%s]', $key, $value['name'], $value['vaccines'][0]['vaccine']['startTime']);
        }, $vaccineList, array_keys($vaccineList))))[0];
        $vaccineId = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['id'];
        $startTime = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime'];
        // 设置需要预约人的信息
        $memberList = $miao->getMemberList();
        $memberIndex = explode('-', $this->chooseAsk('请选择一个预约人身份信息【输入序号】', array_map(function($value, $key) {
            return sprintf('%s-%s[%s]', $key, $value['name'], substr_replace($value['idCardNo'], '************', 4, 12));
        }, $memberList, array_keys($memberList))))[0];
        $memberId = $memberList[$memberIndex]['id'];

        $this->info("您正在为{$memberList[$memberIndex]['name']}预约[{$vaccineList[$vaccineIndex]['name']}]于{$vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime']}的秒杀");
        // 倒计时循环
        $verifyCode = 0;
        $startTimestamp = strtotime($startTime);
        $detail = [];
        if ($startTimestamp > time()) {
            $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
            $this->danger("经过测试，验证码请求+输入时间大概率在5-15秒之间）");
            while($startTimestamp > time()) {
                $second = sprintf('%.2f', $startTimestamp - $this->microtime_float());
                if ($second > 14 && $second < 15) {
                    $this->info("正在请求验证码图片（请注意：图片会让当前终端失去焦点，将优先自动打码，失败后使用手工打码）\n");
                    $vcode = $miao->getValidateCode();
                    if (getenv('CJY_POWER')) {
                        $cjyResult = (new CJY())->process($vcode);
                        if ($cjyResult['err_no'] == 0) {
                            $verifyCode = $cjyResult['pic_str'];
                        }
                    }
                    // 如果远程处理验证码失误，需要开始人工输入验证码
                    if (!$verifyCode) {
                        file_put_contents(__DIR__.'/vcode.jpg', base64_decode($vcode));
                        system('open '.__DIR__.'/vcode.jpg');
                        $verifyCode = $this->ask('请输入图片验证码');
                    }
                } else {
                    $output->write("\r<info>距离开始还有{$second}秒<info>");
                    usleep(5000);
                }
            }
        }
        try {
            $detail = $miao->vaccineDetail($vaccineId);
            $nowDate = date('Y-m-d H:i:s');
            $this->info("在倒计时完毕后，获取到秒杀详情信息[{$nowDate}]");
            var_dump($detail);
        } catch(\Exception $e) {
            $this->danger($e->getMessage());
        }
        // $sign = md5(($startTimestamp * 1000 + 220) . 'healthych.com');
        // $date = '2019-10-25';
        // $result = $miao->fixedSubmit($vaccineId, $memberId, $verifyCode, $sign, $date);
        $result = $miao->submit($vaccineId, $memberId, $verifyCode, $detail);
        var_dump($result);
    }
    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

}