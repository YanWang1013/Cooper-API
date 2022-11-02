<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Drivers extends Model
{
    //

    protected $table="t_drivers";

    protected $fillable = [ 'device_id',
                            'first_name',
                            'last_name',
                            'device_type',
                            'device_token',
                            'email',
                            'password',
                            'phone_number',
                            'gender',
                            'birthday',
                            'api_token',
                            'sms_otp',
                            'sms_verify',
                            'email_otp',
                            'email_verify',
                            'reset_otp',
                            'status',
                            'social_unique_id',
                            'login_by',
                            'fcm_token',
                            'hyperwallet_user_token',
                            'hyperwallet_paypal_token',
                            'hyperwallet_card_token',
                            'quickblox_id',
                            'quickblox_username',
                            'quickblox_password'
                            ];

    public $timestamps=true;


    /**
     * The driver has many documents.
     */
    public function documents()
    {
        return $this->hasMany('App\Http\Models\DriverDocument', 'driver_id');
    }

    /**
     * The driver has one profile.
     */
    public function profile()
    {
        return $this->hasOne('App\Http\Models\DriverProfile', 'driver_id');
    }

}
