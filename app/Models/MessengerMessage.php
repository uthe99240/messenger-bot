<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessengerMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'message',
    ];
}
