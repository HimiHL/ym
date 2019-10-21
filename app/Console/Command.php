<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Command extends BaseCommand
{
    public function __construct()
    {
        parent::__construct();
        $this->setName($this->name)
            ->setDescription($this->description);
        if (isset($this->requireArgument)) {
            foreach ($this->requireArgument as $requireArgument) {
                $this->addArgument($requireArgument['key'], InputArgument::REQUIRED, $requireArgument['intro']);
            }
        }
        if (isset($this->optionalArgument)) {
            foreach ($this->optionalArgument as $optionalArgument) {
                $this->addArgument($optionalArgument['key'], InputArgument::OPTIONAL, $optionalArgument['intro']);
            }
        }
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            '请指定一个配置文件',
            'config.json'
        );
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (file_exists($input->getOption('config'))) {
            // 开始加载JSON配置
            $configJson = file_get_contents($input->getOption('config'));
            $configs = json_decode($configJson);
            foreach ($configs as $key => $value) {
                $key = strtoupper($key);
                putenv("{$key}={$value}");
            }
        }

        $this->output = $output;
        $this->input = $input;
    }


    public function info($str)
    {
        $this->output->writeln("<info>{$str}<info>");
    }

    public function warning($str)
    {
        $this->output->writeln("<comment>{$str}<comment>");
    }

    public function question($str)
    {
        $this->output->writeln("<question>{$str}<question>");
    }

    public function danger($str)
    {
        $this->output->writeln("<error>{$str}<error>");
    }
    public function ask($str)
    {
        return $this->getHelper('question')->ask($this->input, $this->output, new Question("{$str}\n"));
    }

    public function table($headers, $rows)
    {
        $table = new Table($this->output);
        $table->setHeaders($headers)->setRows($rows)->render();
    }

    public function chooseAsk($str, $list)
    {
        $question = new ChoiceQuestion("{$str}\n", $list);
        $question->setErrorMessage('请选择一个选项');
        return $this->getHelper('question')->ask($this->input, $this->output, $question);
    }
}