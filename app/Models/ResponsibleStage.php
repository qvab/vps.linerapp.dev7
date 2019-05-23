<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsibleStage extends Model
{
    public $table = 'resp_stage';
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'fields' => 'array',
        'statuses' => 'array',
    ];

    protected $hidden = [
        'id', 'account_id'
    ];

    protected $fillable = ['account_id', 'fields', 'statuses'];

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }
}
