<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

     protected $fillable = [
        'id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'work_hours',
        'status',
        'notes',
        'created_at',
        'updated_at'
    ];

     protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_hours' => 'decimal:2',
    ];

     public function employee()
    {
        return $this->belongsTo(employees::class, 'employee_id');
    }

     public static function calculateHours($checkIn, $checkOut)
    {
        if (!$checkIn || !$checkOut) return 0;
        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);
        return round($end->floatDiffInMinutes($start) / 60, 2); // hours with 2 decimals
    }
}
