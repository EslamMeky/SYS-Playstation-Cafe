<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionItem extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'session_id',
        'item_id',
        'quantity',
        'price',
        'total',
        'created_at',
        'updated_at'
    ];

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
