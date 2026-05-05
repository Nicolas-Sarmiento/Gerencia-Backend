<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model {

    protected $primaryKey = 'productId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'productId',
        'name',
        'description'
    ];

    public function quotedItems(){
        return $this->hasMany(QuotedItem::class, 'productId', 'productId');
    }

    public function images(){
        return $this->hasMany(ProductImage::class, 'productId', 'productId');
    }

}