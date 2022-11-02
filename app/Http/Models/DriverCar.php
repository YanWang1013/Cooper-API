<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DriverCar extends Model
{
    //

    protected $table="t_driver_car";

    protected $fillable = [ 'driver_id',
                            'status',
                            'car_image',
                            ];

    public $timestamps=true;

}
