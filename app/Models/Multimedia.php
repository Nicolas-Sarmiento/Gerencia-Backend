<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Multimedia extends Model {

    protected $primaryKey = 'multimediaId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'multimediaId',
        'content',
        'resourceUrl',
        'type',
        'articleId'
    ];

    public function article() {
        return $this->belongsTo(Article::class, 'articleId', 'articleId');
    }

}