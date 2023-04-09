<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'uuid',
        'reference',
        'authority',
        'amount',
        'user_id',
        'order_id',
        'status',
        'description'
    ];
}
