<?php

namespace Modules\CRM\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CRM\Database\factories\CrmInformationBankFactory;

class CrmInformationBank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question_text',
        'response_text',
        'user_id',
        'likes_count',
        'shared_count',
        'status'
    ];

    protected static function newFactory(): CrmInformationBankFactory
    {
        //return CrmInformationBankFactory::new();
    }
}
