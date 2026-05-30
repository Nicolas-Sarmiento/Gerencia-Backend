<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model {

    protected $primaryKey = 'articleId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'articleId',
        'title',
        'userId'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId', 'userId');
    }

    public function multimedia(){
        return $this->hasMany(Multimedia::class, 'articleId', 'articleId')->orderBy('order');
    }

}