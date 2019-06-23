<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoTask extends Model
{
    public $timestamps = false;
    public $table = "autotask";

    protected $fillable = [
      "pipeline", 
      "account_id", 
      "statuses", 
      "responsible", 
      "schedule", 
      "task_type", 
      "body", 
      "date_interval", 
      "type_interval",
      "date_days",
      "date_hours",
      "date_min"
    ];

    protected $casts = [
        "statuses" => "array",
        "responsible" => "array",
        "schedule" => "array"
    ];

    protected $hidden = ["account_id"];

    public function account()
    {
        return $this->belongsTo("App\Models\Account");
    }
}
