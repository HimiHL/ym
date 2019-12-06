<?php
namespace App\Console;

use App\Service\Handle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VaccineList extends Command
{
    protected $name = 'list:vacc';
    protected $description = '疫苗列表';
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
        $list = $miao->getVaccines();
        $headers = [
            '预约ID', '医院', '地址', '预约时间', '服务器时间', '本地时间'
        ];
        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                $item['vaccines'][0]['vaccine']['id'], $item['name'], $item['address'], $item['vaccines'][0]['vaccine']['startTime'], date('Y-m-d H:i:s', $item['vaccines'][0]['vaccine']['now'] / 1000), date('Y-m-d H:i:s', time())
            ];
        }
        $this->table($headers, $rows);
        $this->question('服务器和本地时间有时差是正常的，只要差距不大即可，因为是先拉的列表然后计算的本地时间，有时差1-5秒正常');
    }

}