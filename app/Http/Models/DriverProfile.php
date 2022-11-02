<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    //

    protected $table="t_driver_profile";

    protected $fillable = [ 'driver_id',
                            'language',
                            'address',
                            'address_2',
                            'city',
                            'country',
                            'postal_code',
                            'car_number',
                            'car_model',
                            'status',
                            'avatar',
                            'service_type',
                            'service_type_status',
                            'new_service_type',
                            'service_type_new_at',
                            'service_type_approve_at',
                            'service_type_release_at',
                            ];

    public $timestamps=true;

}
