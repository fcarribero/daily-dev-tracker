<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_date',
        'content',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];
}
