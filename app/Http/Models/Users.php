<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    //

    protected $table="t_users";

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
                            'avatar',
                            'fcm_token',
                            'social_unique_id',
                            'login_by',
                            'quickblox_id',
                            'quickblox_username',
                            'quickblox_password'
                            ];

    public $timestamps=true;

}
