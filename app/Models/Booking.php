<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'branch_id',
        'table_id',
        'customer_name',
        'customer_phone',
        'status',
        'start_time',
        'end_time',
        'price_per_hour',
        'total_hours',
        'total_price',
        'notes',
        'created_at',
        'updated_at'
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class,'branch_id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class,'table_id');
    }
}
