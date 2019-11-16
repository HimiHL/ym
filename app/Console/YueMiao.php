<?php
namespace App\Console;

use App\Service\CJY;
use App\Service\Handle;
use App\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class YueMiao extends Command
{
    protected $name = 'ym';
    protected $description = '预约疫苗[自动选择预约日期]';
    protected $requireArgument = [
    ];
    protected $optionalArgument = [
    ];
    protected $requireOption = [
    ];
    protected $option = [
        [
            'key' => 'code', 
            'intro' => '地区代码,成都:5101'
        ],
        [
            'key' => 'vid',
            'intro' => '疫苗ID'
        ],
        [
            'key' => 'mid',
            'intro' => '预约人ID'
        ],
        [
            'key' => 'offsettime',
            'intro' => '偏移毫秒，默认700'
        ]
    ];
    protected $noneOption = [
        [
            'key' => 'multi',
            'intro' => '并发请求'
        ]
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
            '序号', 'ID', '姓名', '身份证号'
        ];

        $offsetTime = $input->getOption('offsettime') ?: 700;
        $offsetTime = 0 + $offsetTime;

        $verifyCode = 0;
        $isMulti = $input->getOption('multi');

        $this->info('并发秒杀开关: '. ($isMulti ? '开' : '关'));
        $this->info('偏移微秒: '. $offsetTime);
        $this->info('超级鹰自动打码状态: '. (getenv('CJY_POWER') ? '开' : '关'));

        // Step1 选择地区
        $regionCode = $input->getOption('code');
        if (!$regionCode) {
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
        }

        // Step2 选择医院
        $vaccineList = $miao->getVaccines(1, $vaccineCode, $regionCode);
        $vaccineId = $input->getOption('vid');
        $startTime = 0;
        if ($vaccineId) {
            foreach ($vaccineList as $vaccine) {
                if (isset($vaccine['vaccines'][0]['vaccine'])) {
                    if ($vaccine['vaccines'][0]['vaccine']['id'] == $vaccineId) {
                        $startTime = $vaccine['vaccines'][0]['vaccine']['startTime'];
                        $startTimeMillSecond = strtotime($startTime) * 1000;
                        break ;
                    }
                }
            }
            if (!$startTime) {
                $this->danger('没有符合条件的医院疫苗');
                exit;
            }
        } else {
            $rows = [];
            foreach ($vaccineList as $key => $item) {
                if (isset($item['vaccines'][0]['vaccine'])) {
                    $rows[] = [
                        $key, $item['vaccines'][0]['vaccine']['id'], $item['name'], "[".Util::getWeek($item['vaccines'][0]['vaccine']['startTime'])."]".$item['vaccines'][0]['vaccine']['startTime']
                    ];
                }
            }
            $this->table($vaccineHeader, $rows);
            $vaccineIndex = $this->ask('请输入序号: ');
            $vaccineId = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['id'];
            $startTime = $vaccineList[$vaccineIndex]['vaccines'][0]['vaccine']['startTime'];
            $startTimeMillSecond = strtotime($startTime) * 1000;
        }

        // Step3 选择预约人
        $linkMenId = $input->getOption('mid');
        if (!$linkMenId) {
            $linkMenList = $miao->getMemberList();
            $rows = [];
            foreach ($linkMenList as $key => $item) {
                $rows[] = [
                    $key, $item['id'], $item['name'], substr_replace($item['idCardNo'], '************', 4, 12)
                ];
            }
            $this->table($linkMenHeader, $rows);
            $linkMenIndex = $this->ask('请输入序号: ');
            $linkMenId = $linkMenList[$linkMenIndex]['id'];
        }

        // Step4 倒计时
        $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
        while($startTimeMillSecond > Util::microtimeInt() + $offsetTime) {
            $hasMillSecond = $startTimeMillSecond - Util::microtimeInt();
            if (!$verifyCode && $hasMillSecond / 1000 > 29 && $hasMillSecond / 1000 < 30) {
                $verifyCode = $this->getVerifyCode($miao);
            }
            $output->write("\r".(new \DateTime())->format('H:i:s:u') . ',剩余' . $hasMillSecond / 1000 . '秒');
            usleep(500);
        }

        // Step5 获取秒杀详情 ...至关重要的一步
        try {
            $detail = $miao->vaccineDetail($vaccineId);
        } catch(\Exception $e) {
            $this->danger($e->getMessage());
            if ($e->getCode() === 5555) {
                $detail = $miao->vaccineDetail($vaccineId);
            }
        }

        // Step6 秒杀
        $results = [];
        $sign = md5($detail['time'] . 'fuckhacker10000times');
        $days = array_reverse($detail['days']);
        foreach ($days as $day) {
            if ($day['total'] > 0) {
                $workDate = date('Y-m-d', strtotime($day['day']));
                if (!$verifyCode) {
                    $verifyCode = $this->getVerifyCode($miao);
                }
                if ($isMulti) {
                    $miao->multiSubmit($vaccineId, $linkMenId, $verifyCode, $workDate, $sign);
                } else {
                    $results[] = $miao->submit($vaccineId, $linkMenId, $verifyCode, $workDate, $sign);
                }
                $verifyCode = 0;
            }
        }

        $this->info(json_encode($detail));
        $this->info("秒杀结果");
        $this->info(json_encode($detail));
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