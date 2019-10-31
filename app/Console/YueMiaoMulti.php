<?php
namespace App\Console;

use App\Service\CJY;
use App\Service\Handle;
use App\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class YueMiaoMulti extends Command
{
    protected $name = 'ym:multi';
    protected $description = '预约疫苗[并发预约]';
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
        $regionCode = 0;
        $regionList = [];
        $regionHeader = [
            '序号', '地区', '地区代码'
        ];


        $vaccineCode = 8803;
        $vaccineList = [];
        $vaccineHeader = [
            '序号', '预约ID', '医院', '预约时间'
        ];

        $linkMenId = 0;
        $linkMenList = [];
        $linkMenHeader = [
            '序号', '姓名', '身份证号'
        ];

        $workDate = '';
        $workDateList = [];
        $workDateHeader = [
            '序号', '日期'
        ];

        $verifyCode = 0;

        $this->info('超级鹰自动打码状态: '. (getenv('CJY_POWER') ? '开' : '关'));
        // Step1 选择地区
        for ($i = 0; $i <= 1; $i++) {
            $regionList = $miao->getRegions($regionCode);
            $rows = [];
            foreach ($regionList as $key => $region) {
                $rows[] = [
                    $key, $region['name'], $region['value']
                ];
            }
            $this->table($regionHeader, $rows);
            $regionIndex = $this->ask('请输入序号:');
            $regionCode = $regionList[$regionIndex]['value'];
        }

        // Step2 选择医院
        $vaccineList = $miao->getVaccines(1, $vaccineCode, $regionCode);
        $rows = [];
        foreach ($vaccineList as $key => $item) {
            $rows[] = [
                $key, $item['vaccines'][0]['vaccine']['id'], $item['name'], "[".Util::getWeek($item['vaccines'][0]['vaccine']['startTime'])."]".$item['vaccines'][0]['vaccine']['startTime']
            ];
        }
        $this->table($vaccineHeader, $rows);
        $vaccineIndex = $this->ask('请输入序号: ');
        $vaccineId = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['id'];
        $startTime = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime'];
        $startTimeMillSecond = strtotime($startTime) * 1000;

        // Step3 选择预约人
        $linkMenList = $miao->getMemberList();
        $rows = [];
        foreach ($linkMenList as $key => $item) {
            $rows[] = [
                $key, $item['name'], substr_replace($item['idCardNo'], '************', 4, 12)
            ];
        }
        $this->table($linkMenHeader, $rows);
        $linkMenIndex = $this->ask('请输入序号: ');
        $linkMenId = $linkMenList[$linkMenIndex]['id'];

        $this->info("您正在为{$linkMenList[$linkMenIndex]['name']}预约疫苗，[{$vaccineList[$vaccineIndex]['name']}]将于{$startTime}开始");
        // Step4 倒计时
        $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
        while($startTimeMillSecond > Util::microtimeInt() + 6) {
            $hasMillSecond = $startTimeMillSecond - Util::microtimeInt();
            if (!$verifyCode && $hasMillSecond / 1000 > 14 && $hasMillSecond / 1000 < 15) {
                $verifyCode = $this->getVerifyCode($miao);
            }
            $output->write("\r".(new \DateTime())->format('H:i:s:u'));
            usleep(500);
        }
        // Step5 获取秒杀详情 ...至关重要的一步
        try {
            $detail = $miao->vaccineDetail($vaccineId);
            $this->info("在倒计时完毕后，获取到秒杀详情信息");
            $this->info(json_encode($detail));
        } catch(\Exception $e) {
            $this->danger($e->getMessage());
        }
        // Step6 秒杀
        $sign = md5($detail['time'] . 'fuckhacker10000times');
        foreach ($detail['days'] as $day) {
            if ($day['total'] > 0) {
                $workDate = date('Y-m-d', strtotime($day['day']));
                if (!$verifyCode) {
                    $verifyCode = $this->getVerifyCode($miao);
                }
                $this->info("{$workDate}剩余{$day['total']}的并发秒杀:");
                $miao->multiSubmit($vaccineId, $linkMenId, $verifyCode, $workDate, $sign);
                $verifyCode = 0;
            }
        }
    }
    
    public function getVerifyCode(&$miao)
    {
        $verifyCode = 0;
        $this->info("开始获取验证码");
        $vcode = $miao->getValidateCode();
        if (getenv('CJY_POWER')) {
            $cjyResult = (new CJY())->process($vcode);
            if ($cjyResult['err_no'] == 0) {
                $verifyCode = $cjyResult['pic_str'];
                $this->info("验证码解析为: {$verifyCode}\n");
            }
        }
        // 如果远程处理验证码失误，需要开始人工输入验证码
        if (!$verifyCode) {
            file_put_contents(__DIR__.'/vcode.jpg', base64_decode($vcode));
            system('open '.__DIR__.'/vcode.jpg');
            $verifyCode = $this->ask('请输入图片验证码:');
        }
        return $verifyCode;
    }
}