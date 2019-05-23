<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model {
    public $timestamps = false;

    protected $fillable = ['entity', 'entity_id', 'quantity', 'product_id'];

    public function product()
    {
    	return $this->belongsTo('App\Models\Product');
    }
}
