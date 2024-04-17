<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Modules\Birth\CommissionController;

class CommissionBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $site_id;
    protected $type;
    protected $config;

    /**
     * Create a new job instance.
     */
    public function __construct($site_id, $type, $config)
    {
        // dd('test');
        $this->site_id = $site_id;
        $this->type = $type;
        $this->config = $config;
    }

    /**
     * Execute the job.
     */
    public function handle(CommissionController $birth): void
    {
        $birth->giveBirthToCommissions($this->site_id, $this->type, $this->config);
    }
}
