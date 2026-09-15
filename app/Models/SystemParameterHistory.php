<?php

namespace App\Models;

class SystemParameterHistory extends BaseModel
{
    public $timestamps = true;

    protected $fillable = [
        'system_parameter_id', 'user_id', 'previous_value', 'new_value',
        'previous_active', 'new_active',
    ];

    protected $casts = [
        'previous_active' => 'boolean',
        'new_active' => 'boolean',
    ];

    public function parameter()
    {
        return $this->belongsTo(SystemParameter::class, 'system_parameter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
