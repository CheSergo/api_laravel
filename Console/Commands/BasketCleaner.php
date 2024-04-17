<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Basket\Cleaner;

class BasketCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basket:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Cleaner $commander): void
    {
        $commander->janitor();
    }
}
