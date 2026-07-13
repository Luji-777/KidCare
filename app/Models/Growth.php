<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Child;

class Growth extends Model
{
    protected $fillable = [
        'child_id',
        'height',
        'weight',
        'date'
    ];
    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
