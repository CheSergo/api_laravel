<?php

use Illuminate\Database\Eloquent\Model;

class SiteSource extends Model
{
    protected $table = 'site_sources';

    protected $connection = 'mariadb';
}