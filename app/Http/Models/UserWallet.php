<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table="t_user_wallet";

    protected $fillable = [
        'user_id', 'amount', 'status', 'via',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at'
    ];

    public $timestamps=true;
}
