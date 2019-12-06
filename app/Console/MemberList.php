<?php
namespace App\Console;

use App\Service\Handle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MemberList extends Command
{
    protected $name = 'list:member';
    protected $description = '用户信息列表';
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
        $list = $miao->getMemberList();
        $headers = [
            '身份ID', '姓名', '身份证号码', '性别', '用户ID'
        ];
        $rows = [];
        foreach ($list as $item) {
           $rows[] = [
               $item['id'], $item['name'], $item['idCardNo'], $item['sex'] == 1 ? '男' : '女', $item['userId']
            ];
        }
        $this->table($headers, $rows);
    }

}