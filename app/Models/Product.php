<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'stock',
    ];

    public function orderDetail(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
