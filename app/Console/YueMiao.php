<?php
namespace App\Console;

use App\Service\CJY;
use App\Service\Handle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class YueMiao extends Command
{
    protected $name = 'ym';
    protected $description = '约苗预约';
    protected $requireArgument = [
        [
            'key' => 'MemberId',
            'intro' => '身份ID，通过php artisan MemberList中提取的`身份ID`字段'
        ],
        [
            'key' => 'VaccineId',
            'intro' => '预约ID，通过php artisan VaccineList中提取的`预约ID`字段'
        ],
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
        $memberId = intval($input->getArgument('MemberId'));
        $vaccineId = intval($input->getArgument('VaccineId'));

        $this->info("正在为ID为{$memberId}的用户抢购预约ID为{$vaccineId}的疫苗，请稍后在提示后输入验证码...");

        $vcode = $miao->getValidateCode();
        $verifyCode = 0;
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

        $detail = [];
        try {
            $detail = $miao->vaccineDetail($vaccineId);
            $nowDate = date('Y-m-d H:i:s');
            $this->info("在倒计时完毕后，获取到秒杀详情信息[{$nowDate}]");
            var_dump($detail);
        } catch(\Exception $e) {
            $this->danger($e->getMessage());
        }
        $result = $miao->submit($vaccineId, $memberId, $verifyCode, $detail);
        var_dump($result);
    }

}