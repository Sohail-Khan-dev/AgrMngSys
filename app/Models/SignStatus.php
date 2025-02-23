<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignStatus extends Model
{
    protected $fillable = [
        'user_id',
        'agreement_id',
        'status',
        'signature'
    ];
    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
