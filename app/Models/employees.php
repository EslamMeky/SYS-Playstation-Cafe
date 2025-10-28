<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employees extends Model
{
    use HasFactory;

    protected $fillable=[
        'id',
        'user_id',
        'name',
        'phone',
        'position',
        'salary',
        'active',
        'join_date',
        'created_at',
        'updated_at'
    ];

    public $timestamps=true;

    protected $casts=[
        'active'=>'boolean',
        'jouin_date'=>'date',
        'salary'=>'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

     public function attendances()
    {
        return $this->hasMany(Attendance::class,'employee_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class,'employee_id');
    }

    // // helper: get display name (user.name if exists else name)
    public function getDisplayNameAttribute()
    {
        return $this->user ? $this->user->name : $this->name;
    }


}
