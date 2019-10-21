<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command;

class Kernel extends Command
{
    public $commands = [
        NBYueMiao::class,
        YueMiao::class,
        VaccineList::class,
        MemberList::class
    ];
}