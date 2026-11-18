<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TobaccoReceipt extends Model
{
    protected $fillable = ['tobacco_id', 'grams', 'price_per_gram', 'supplier', 'received_at', 'user_id'];

    protected $casts = ['received_at' => 'date'];
}
