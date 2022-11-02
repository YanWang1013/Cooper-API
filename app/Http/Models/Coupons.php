<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Coupons extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $table="t_coupons";

    protected $fillable =   [   'coupon_code',
                                'discount',
                                'discount_type',
                                'expiration',
                                'status',
                                'user_id',
                                'added_at',
                                'expired_at',
                                'used_at'
                            ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public $timestamps=true;
}
