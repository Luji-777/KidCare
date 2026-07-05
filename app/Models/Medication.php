<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\medicalRecord;


class Medication extends Model
{   
    protected $guarded=[];

    public function record(){
        return $this->belongsTo(medicalRecord::class, 'record_id');
    }
}
