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
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $miao = new Handle;
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