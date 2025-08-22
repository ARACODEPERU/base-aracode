<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintsBookAttention extends Model
{
    protected $fillable = [
        'complaints_book_id',
        'user_id',
        'status',
        'date_start',
        'date_end',
        'priority',
        'internal_management_notes',
        'consumer_response',
        'attached_file',
        'means_communication'
    ];
}
