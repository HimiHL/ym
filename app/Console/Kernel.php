<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command;

class Kernel extends Command
{
    public $commands = [
        YueMiao::class,
        YueMiaoForce::class,
        YueMiaoAuto::class,
        VaccineList::class,
        MemberList::class,
    ];
}