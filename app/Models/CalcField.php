<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalcField extends Model
{
    public $timestamps = false;

    protected $casts = [
        'fields' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }
}
