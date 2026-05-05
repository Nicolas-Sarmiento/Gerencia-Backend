<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model {

    protected $table = 'images';
    protected $primaryKey = 'imageId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'imageId',
        'imageurl',
        'alt',
        'productId',
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'productId', 'productId');
    }

}