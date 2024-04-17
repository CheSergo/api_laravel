<?php

namespace App\Modules\Logs;

use Closure;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class Test implements LoggablePipe
{
    public function __construct(protected string $field){}

    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        dd($this->field);
        dd('tr');
        Arr::forget($event->changes, ["attributes.{$this->field}", "old.{$this->field}"]);

        return $next($event);
    }
}