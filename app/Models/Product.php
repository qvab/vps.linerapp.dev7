<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = ['counter'];

    public function links()
    {
        return $this->hasMany('App\Models\Link');
    }
}
