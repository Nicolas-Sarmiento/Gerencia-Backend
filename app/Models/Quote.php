<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model {

    protected $primaryKey = 'quoteId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'quoteId',
        'clientId',
        'requestDate',
        'status',
        'description'
    ];

    public function client() {
        return $this->belongsTo(Client::class, 'clientId', 'clientId');
    }

    public function quoted_Items() {
        return $this->hasMany(QuoteItem::class, 'quoteId', 'quoteId');
    }

    

}