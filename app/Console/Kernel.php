<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected $commands = [

        \App\Console\Commands\Test::class,

        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\TaskWork::class,
        \App\Console\Commands\TaskSelectWork::class,
        \App\Console\Commands\TaskPublicity::class,

        \App\Console\Commands\TaskComment::class,
        \App\Console\Commands\TaskNoStick::class,
        
        \App\Modules\Install\Console\Commands\InstallKPPW::class,

        
        \App\Console\Commands\VersionMigration::class,
        
        \App\Console\Commands\UpdateKPPW::class,

        \App\Console\Commands\EmployAccept::class,
        \App\Console\Commands\EmployDelivery::class,
        \App\Console\Commands\EmployComment::class,
        \App\Console\Commands\EmployDeadline::class,

        \App\Console\Commands\BuyGoods::class,
        \App\Console\Commands\GoodsComment::class,
        \App\Console\Commands\Vipshop::class,
        \App\Console\Commands\Vipupdate::class,

        
        \App\Console\Commands\TaskBidComment::class,
        
        \App\Console\Commands\TaskPayDelay::class,
        \App\Console\Commands\TaskApplyDelay::class,
        \App\Console\Commands\TaskBidSelectWork::class,
        
        \App\Console\Commands\TaskComplateTrue::class,
        \App\Console\Commands\SystemSort::class,
        //\App\Console\Commands\CreateTaskWater::class,

    ];

    
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
                 ->everyMinute();
        $schedule->command('taskWork')
            ->everyMinute();
        $schedule->command('taskSelectWork')
            ->everyMinute();
        $schedule->command('taskPublicity')
            ->everyMinute();
        $schedule->command('TaskApplyDelay')
            ->everyMinute();
        $schedule->command('taskComment')
            ->everyMinute();
        $schedule->command('taskNoStick')
            ->everyMinute();
        $schedule->command('EmployAccept')
            ->everyMinute();
        $schedule->command('EmployComment')
            ->everyMinute();
        $schedule->command('EmployDeadline')
            ->everyMinute();
        $schedule->command('EmployDelivery')
            ->everyMinute();
        $schedule->command('BuyGoods')
            ->everyMinute();
        $schedule->command('GoodsComment')
            ->everyMinute();
        $schedule->command('vipshop')
            ->everyMinute();
        $schedule->command('taskBidComment')
            ->everyMinute();
        $schedule->command('TaskPayDelay')
            ->everyMinute();
        $schedule->command('taskBidSelectWork')
            ->everyMinute();
        $schedule->command('TaskComplateTrue')
            ->everyMinute();
        $schedule->command('test')
            ->everyMinute();
        $schedule->command('SystemSort')
            ->everyMinute();
    }
}