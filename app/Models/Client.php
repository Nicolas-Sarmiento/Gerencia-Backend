<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model {

    protected $primaryKey = 'clientId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'clientId',
        'name',
        'phone',
        'mail'
    ];

    public function quotes() {
        return $this->hasMany(Quote::class, 'clientId', 'clientId');
    }

}