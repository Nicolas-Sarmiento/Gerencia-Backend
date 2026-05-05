<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualProcessedWaste extends Model {

    protected $primaryKey = 'wasteId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'wasteId',
        'year',
        'processedWaste'
    ];

}