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
        [
            'key' => 'token',
            'intro' => '约苗Token'
        ]
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
            'key' => 'mode',
            'intro' => '模式，1随机日期，2所有日期，默认1'
        ],
        [
            'key' => 'retry',
            'intro' => '重试次数，默认0'
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
        $token = $input->getOption('token') ?: false;
        if (!$token) {
            $this->info("请给我Token");
            exit;
        }

        $miao = new Handle($token);
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

        $mode = $input->getOption('mode') ?: 1;
        $mode = 0 + $mode;

        $retry = $input->getOption('retry') ?: 0;
        $retry = 0 + $retry;

        $verifyCode = 0;
        $isMulti = $input->getOption('multi');

        $this->info('并发秒杀开关: '. ($isMulti ? '开' : '关'));
        $this->info('模式: '. $mode == 1 ? '随机日期' : '所有日期');
        $this->info('超级鹰自动打码状态: '. (getenv('CJY_POWER') ? '开' : '关'));
        $this->info("将在秒杀一次失败后重试{$retry}次");

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

        $verifyCodeRandomSecond = rand(25, 40);
        $this->info("将提前{$verifyCodeRandomSecond}秒获取验证码");

        $randomTime = rand(45, 60);
        $this->info("预计开始前{$randomTime}微秒执行查询");
        // Step4 倒计时
        $this->danger("活动将于{$startTime}开始，正在倒计时中..（请注意在剩余15秒左右需要输入验证码，务必时刻关注）");
        while($startTimeMillSecond > Util::microtimeInt() + $randomTime) {
            $hasMillSecond = $startTimeMillSecond - Util::microtimeInt();
            if (!$verifyCode && $hasMillSecond / 1000 > ($verifyCodeRandomSecond - 1) && $hasMillSecond / 1000 < $verifyCodeRandomSecond) {
                $verifyCode = $this->getVerifyCode($miao);
            }
            $output->write("\r[".(new \DateTime())->format('H:i:s:u') . ']剩余' . $hasMillSecond / 1000 . '秒');
            usleep(500);
        }

        // 获取秒杀详情
        try {
            $detail = [];
            $j = 0;
            while(true) {
                $j++;
                $result = $miao->moreTimesVaccineDetail($vaccineId);
                if ($result['code'] == '0000') {
                    $detail = $result['data'];
                    break;
                }
                if ($j > 1000) {
                    break;
                }
                usleep(10);
            }
        } catch(\Exception $e) {
            $this->danger('获取秒杀详情时遇到了异常: ' . $e->getMessage());
        }

        $this->info("获取详情{$j}次");
        $this->info("可选的查询日期: " . json_encode($detail['days'] ?? []));
        $this->info("排队时间: {$detail['time']} || " . sprintf('%s.%s',date('Y-m-d H:i:s',$detail['time'] / 1000), substr($detail['time'], 10, 3)));

        $sleepTime = 999999;
        $this->info("将在查询前模拟暂停{$sleepTime}微秒");
        if ($sleepTime > 0) {
            usleep($sleepTime);
        }
        // Step6 秒杀
        $exceptions = [];
        try {
            $sign = md5($detail['time'] . 'fuckhacker10000times');
            if ($mode == 1) {
                $index = rand(0, count($detail['days'])-1);
                $day = $detail['days'][$index];
                $workDate = date('Y-m-d', strtotime($day['day']));
                $this->info("日期: " . $workDate);
                // 根据用户配置的重试次数开始
                for ($i = 0; $i <= $retry; $i++) {
                    if (!$verifyCode) {
                        $verifyCode = $this->getVerifyCode($miao);
                    }
                    $result = $miao->submit($vaccineId, $linkMenId, $verifyCode, $workDate, $sign);
                    $this->info("结果: " . json_encode($result));
                    if ($result['ok']) {
                        $this->info("恭喜！您已查询到{$workDate}的疫苗！");
                        break;
                    }
                    $verifyCode = 0;
                }
            } elseif ($mode == 2) {
                foreach (($detail['days'] ?? '') as $day) {
                    if ($day['total'] > 0) {
                        $workDate = date('Y-m-d', strtotime($day['day']));
                        $this->info("日期: " . $workDate);
                        // 根据用户配置的重试次数开始
                        for ($i = 0; $i <= $retry; $i++) {
                            if (!$verifyCode) {
                                $verifyCode = $this->getVerifyCode($miao);
                            }
                            $result = $miao->submit($vaccineId, $linkMenId, $verifyCode, $workDate, $sign);
                            $this->info("结果: " . json_encode($result));
                            if ($result['ok']) {
                                $this->info("恭喜！您已查询到{$workDate}的疫苗！");
                                break 2;
                            }
                            $verifyCode = 0;
                        }
                    }
                }
            }
        } catch(\Exception $e) {
            $exceptions[] = Util::buildException($e);
        }

        $this->info("秒杀结果:");
        $this->info(json_encode($detail));

        $this->danger("异常错误: " . json_encode($exceptions), false);
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