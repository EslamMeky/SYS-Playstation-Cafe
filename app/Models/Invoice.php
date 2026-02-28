<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'session_id',
        'session_total',
        'items_total',
        'discount',
        'final_total',
        'payment_method',
        'status',
        'created_at',
        'updated_at',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
