<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
   public function customer():BelongsTo
   {
    return $this->belongsTo(Customer::class);
   }

   protected $fillable = [
        'customer_id',
        'date',
        'total_price',
    ];

    public function orderDetail(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
