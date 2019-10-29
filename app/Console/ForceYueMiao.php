<?php
namespace App\Console;

use App\Service\CJY;
use App\Service\Handle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ForceYueMiao extends Command
{
    protected $name = 'fym';
    protected $description = '暴力流程式约苗预约';
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
                $key, $item['vaccines'][0]['vaccine']['id'], $item['name'], $item['vaccines'][0]['vaccine']['startTime']
            ];
        }
        $this->table($vaccineHeader, $rows);
        $vaccineIndex = $this->ask('请输入序号: ');
        $vaccineId = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['id'];
        $departmentCode = $vaccineList[$vaccineIndex]['code'];
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
        // Step4 选择日期
        $workDateList = $miao->getWorkDays($departmentCode, $vaccineCode, $vaccineId, $linkMenId);
        $rows = [];
        foreach ($workDateList['dateList'] as $key => $item) {
            $rows[] = [
                $key, $item
            ];
        }
        $this->table($workDateHeader, $rows);
        $dateIndex = $this->ask('请选择日期[输入序号]: ');
        $workDate = $workDateList['dateList'][$dateIndex];

        $this->info("您正在为{$linkMenList[$linkMenIndex]['name']}预约【{$workDate}】的疫苗，[{$vaccineList[$vaccineIndex]['name']}]将于{$startTime}开始");

        // Step5 倒计时
        $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
        while($startTimeMillSecond > $this->microtime_int() + 3) {
            $hasMillSecond = $startTimeMillSecond - $this->microtime_int();
            if (!$verifyCode && $hasMillSecond / 1000 > 14 && $hasMillSecond / 1000 < 15) {
                $verifyCode = $this->ask('输入验证码');
            }
            $output->write("\r".(new \DateTime())->format('H:i:s:u'));
            usleep(500);
        }
        // Step6 秒杀
        $result = $miao->multiSubmit($vaccineId, $linkMenId, $workDate);
        var_dump($result);


        try {
            $detail = $miao->vaccineDetail($vaccineId);
            $this->info("在倒计时完毕后，获取到秒杀详情信息");
            var_dump($detail);
        } catch(\Exception $e) {
            $this->danger($e->getMessage());
        }
    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function microtime_int()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (int)(((float)$usec + (float)$sec) * 1000);
    }
}