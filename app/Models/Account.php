<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $table = 'account';

    protected $fillable = ['subdomain', 'login', 'hash'];
    protected $hidden = ['id', 'subdomain', 'login', 'hash', 'tz'];

    public function distribution()
    {
        return $this->hasOne('App\Models\Distribution');
    }

    public function autoTask()
    {
        return $this->hasMany('App\Models\AutoTask');
    }

    public function calc()
    {
        return $this->hasOne('App\Models\CalcField');
    }

    public function respStage()
    {
        return $this->hasOne('App\Models\ResponsibleStage');
    }

    public function getRouteKeyName()
    {
        return 'subdomain';
    }
}
