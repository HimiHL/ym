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
        [
            'key' => 'RegionCode',
            'intro' => '地区代码'
        ],
        [
            'key' => 'VaccineCode',
            'intro' => '疫苗代码'
        ]
    ];
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $regionCode = $input->getArgument('RegionCode') ?? 5101;
        $vaccineCode = $input->getArgument('VaccineCode') ?? 8803;

        $this->info('超级鹰自动打码状态: '. (getenv('CJY_POWER') ? '开' : '关'));
        $miao = new Handle;
        // 设置医院预约信息，获取秒杀开始时间
        $vaccineList = $miao->getVaccines(1, $vaccineCode, $regionCode);
        $headers = [
            '序号', '预约ID', '医院', '预约时间'
        ];
        $rows = [];
        foreach ($vaccineList as $key => $item) {
            $rows[] = [
                $key, $item['vaccines'][0]['vaccine']['id'], $item['name'], $item['vaccines'][0]['vaccine']['startTime']
            ];
        }
        $this->table($headers, $rows);
        $vaccineIndex = $this->ask('请输入序号: ');
        $vaccineId = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['id'];
        $departmentCode = $vaccineList[$vaccineIndex]['code'];
        $startTime = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime'];
        $startTimestamp = strtotime($startTime);
        $startMillSecond = $startTimestamp * 1000;
        // 设置需要预约人的信息
        $memberList = $miao->getMemberList();
        $headers = [
            '序号', '姓名', '身份证号'
        ];
        $rows = [];
        foreach ($memberList as $key => $item) {
            $rows[] = [
                $key, $item['name'], substr_replace($item['idCardNo'], '************', 4, 12)
            ];
        }
        $this->table($headers, $rows);
        $memberIndex = $this->ask('请输入序号: ');
        $memberId = $memberList[$memberIndex]['id'];

        $workDays = $miao->getWorkDays($departmentCode, $vaccineCode, $vaccineId, $memberId);
        $headers = [
            '序号', '日期'
        ];
        $rows = [];
        foreach ($workDays['dateList'] as $key => $item) {
            $rows[] = [
                $key, $item
            ];
        }
        $this->table($headers, $rows);
        $dateIndex = $this->ask('请选择日期[输入序号]: ');
        $date = $workDays['dateList'][$dateIndex];

        $this->info("您正在为{$memberList[$memberIndex]['name']}预约【{$date}】的疫苗，[{$vaccineList[$vaccineIndex]['name']}]将于{$vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime']}开始");
        // 倒计时循环
        $verifyCode = 0;
        $detail = [];
        if ($startMillSecond > $this->microtime_int()) {
            $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
            $this->danger("经过测试，验证码请求+输入时间大概率在5-15秒之间）");
            while($startMillSecond > $this->microtime_int() + 500) {
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
                        $verifyCode = $this->ask('请输入图片验证码:');
                    }
                } else {
                    $output->write("\r距离开始还有{$second}秒");
                    usleep(1000);
                }
            }
        }
        // try {
        //     $detail = $miao->vaccineDetail($vaccineId);
        //     $this->info("在倒计时完毕后，获取到秒杀详情信息");
        //     var_dump($detail);
        // } catch(\Exception $e) {
        //     $this->danger($e->getMessage());
        // }
        $result = $miao->fixedSubmit($vaccineId, $memberId, $verifyCode, $date);
        var_dump($result);
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