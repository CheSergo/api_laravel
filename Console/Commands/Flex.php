<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\TestController;

class Flex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:flex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(TestController $commander)
    {
        $commander->flex();
    }
}
