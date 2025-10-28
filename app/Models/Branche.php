<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branche extends Model
{
    use HasFactory;

    protected $fillable =[
        'id',
        'name',
        'type',
        'location',
        'phone',
        'status',
        'settings',
        'created_at',
        'updated_at'
    ];
    protected $casts = [
        'settings' => 'array',
    ];

    public function tables()
    {
        return $this->hasMany(Table::class,'branch_id');
    }

}
