<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

     protected $table = 'tables';

    protected $fillable = [
        'id',
        'branch_id',
        'name',
        'type',
        'capacity',
        'price_per_hour',
        'min_hours',
        'status',
        'notes',
        'created_at',
        'updated_at'
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }

    // public function reservations()
    // {
    //     return $this->hasMany(\App\Models\Reservation::class);
    // }
}
