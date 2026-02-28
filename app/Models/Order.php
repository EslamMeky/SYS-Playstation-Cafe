<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'branch_id',
        'session_id',
        'customer_name',
        'customer_phone',
        'type',
        'total_price',
        'status',
        'payment_method',
        'created_at',
        'updated_at'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branche::class, 'branch_id');
    }
}
