<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model {

    protected $table = 'quoteItems';
    protected $primaryKey = 'itemquoteId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'itemquoteId',
        'quoteId',
        'productId',
        'quantity',
        'requestDate',
        'status',
        'description',
    ];

    public function quote() {
        return $this->belongsTo(Quote::class, 'quoteId', 'quoteId');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'productId', 'productId');
    }

    

}