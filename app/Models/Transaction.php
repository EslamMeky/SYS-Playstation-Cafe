<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'employee_id',
        'type',
        'amount',
        'reason',
        'date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

     public function employee()
    {
        return $this->belongsTo(employees::class, 'employee_id');
    }
}
