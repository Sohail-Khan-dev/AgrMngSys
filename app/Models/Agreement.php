<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $fillable = [
        'user_id',
        'agreement_id',
        'status',
        'signature'
    ];
    public function signStatus()
    {
        return $this->hasMany(SignStatus::class);
    }
    public function user()
    {
        return $this->hasMany(User::class);
    }
}
