<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'table_id',
        'customer_name',
        'start_time',
        'end_time',
        'total_hours',
        'price_per_hour',
        'total_amount',
        'status',
        'notes',
        'created_at',
        'updated_-at'
    ];

    public $timestamps=true;

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
