<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Rides extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table="t_rides";

    protected $fillable = [
        'coupon_id',
        'status',
        'canceled_at',
        'cancel_reason',
        'cancel_by',
        'distance',
        'rental_hours',
        'distance_fee',
        'tax_fee',
        'discount_fee',
        'cooper_fee',
        'cooper_tax_fee',
        'pay_amount',
        'user_start_balance',
        'user_end_balance',
        'driver_start_balance',
        'driver_start_balance',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */

    public $timestamps=true;
}
