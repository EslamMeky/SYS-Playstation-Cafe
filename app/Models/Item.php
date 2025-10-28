<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'branch_id',
        'name',
        'category',
        'price',
        'stock',
        'available',
        'notes',
        'created_at',
        'updated_at'
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class, 'branch_id');
    }
}
